<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Backup;

use App\Module\Admin\Infrastructure\Backup\Command\EncryptExistingBackupsCommand;
use App\Module\Admin\Infrastructure\Backup\Command\RunDueBackupsCommand;
use App\Module\Admin\UI\Backup\Controller\AdminBackupController;
use App\Module\Admin\UI\Backup\Controller\MaintenanceModeSubscriber;
use App\Module\Admin\UI\Backup\Controller\SystemStatusController;
use App\Module\Admin\Application\Backup\Service\BackupEncryptionService;
use App\Module\Admin\Application\Backup\Service\BackupFileStorage;
use App\Module\Admin\Application\Backup\Service\BackupStatusProvider;
use App\Module\Admin\Application\Backup\Service\BackupStateStore;
use App\Module\Admin\Application\Backup\Service\DatabaseBackupDumper;
use App\Module\Admin\Application\Backup\Service\MaintenanceModeService;
use App\Module\Admin\Application\Backup\Service\RunBackupHandler;
use App\Module\Admin\Application\Backup\Service\RunDueBackupsHandler;
use App\Module\Admin\Application\Backup\Service\UpdateBackupSettingsHandler;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\Validation;

final class AdminBackupModuleCompletionTest extends TestCase
{
    public function testControllerServicesSubscriberAndCommands(): void
    {
        $projectDir = $this->projectDir();
        $maintenance = new MaintenanceModeService($projectDir);
        $states = new BackupStateStore($projectDir);
        $files = new BackupFileStorage($projectDir);
        $keyFile = $projectDir.'/var/backup.key';
        file_put_contents($keyFile, sodium_bin2base64(random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES), SODIUM_BASE64_VARIANT_ORIGINAL));
        $encryption = new BackupEncryptionService($keyFile);
        $database = new DatabaseBackupDumper($projectDir, 'mysql://user:pass@localhost:3306/app');
        $statusProvider = new BackupStatusProvider($projectDir, $maintenance, $states, $files, $database);
        $updateSettings = new UpdateBackupSettingsHandler($states, $files, $statusProvider);
        $runBackup = new RunBackupHandler($states, $files, $encryption, $database, $statusProvider);
        $runDue = new RunDueBackupsHandler($states, $runBackup);
        $controller = new AdminBackupController($statusProvider, $updateSettings, $runBackup, $maintenance, $this->validator());

        self::assertFalse($maintenance->isEnabled());
        self::assertSame(200, (new SystemStatusController($maintenance))()->getStatusCode());
        self::assertArrayHasKey('settings', $controller->status()->getContent() ? json_decode($controller->status()->getContent(), true, 512, JSON_THROW_ON_ERROR)['data'] : []);
        self::assertSame(200, $controller->settings($this->jsonRequest(['enabled' => true, 'intervalHours' => 12, 'retentionCount' => 2], 'PATCH'))->getStatusCode());
        self::assertSame(500, $controller->settings(Request::create('/', 'PATCH', server: [], content: '{bad'))->getStatusCode());
        $states->write(['settings' => ['enabled' => true, 'intervalHours' => 12, 'retentionCount' => 2], 'history' => [], 'lastSuccessfulRunAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)]);
        self::assertNull($runDue->runDue());
        $states->write(['settings' => ['enabled' => false], 'history' => [['id' => 'x'], 'bad'], 'lastSuccessfulRunAt' => 'bad-date']);
        self::assertNull($runDue->runDue());
        self::assertNull($states->date(null));
        self::assertInstanceOf(\DateTimeImmutable::class, $states->date('2026-08-01T10:00:00+00:00'));
        self::assertSame([], $states->history('bad'));
        self::assertFalse($states->outputSettings($states->settings([]), [])['enabled']);
        try {
            $states->mergeSettings($states->settings([]), ['intervalHours' => 0]);
            self::fail('Expected invalid interval.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('intervalHours', $exception->getMessage());
        }
        self::assertSame(409, $controller->run()->getStatusCode());
        self::assertSame(200, $controller->maintenance($this->jsonRequest(['enabled' => true, 'message' => 'Maintenance'], 'POST'))->getStatusCode());
        self::assertTrue($maintenance->isEnabled());
        self::assertSame(500, $controller->maintenance(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());

        $subscriber = new MaintenanceModeSubscriber($maintenance);
        self::assertArrayHasKey('kernel.request', MaintenanceModeSubscriber::getSubscribedEvents());
        $event = $this->requestEvent(Request::create('/api/orders'));
        $subscriber->onKernelRequest($event);
        self::assertTrue($event->hasResponse());
        $allowed = $this->requestEvent(Request::create('/api/admin/backups'));
        $subscriber->onKernelRequest($allowed);
        self::assertFalse($allowed->hasResponse());
        $subRequest = $this->requestEvent(Request::create('/api/orders'), HttpKernelInterface::SUB_REQUEST);
        $subscriber->onKernelRequest($subRequest);
        self::assertFalse($subRequest->hasResponse());
        $nonApi = $this->requestEvent(Request::create('/home'));
        $subscriber->onKernelRequest($nonApi);
        self::assertFalse($nonApi->hasResponse());

        file_put_contents($files->pathFor(new \DateTimeImmutable('2026-08-01T10:00:00+00:00')), 'first');
        file_put_contents($files->pathFor(new \DateTimeImmutable('2026-08-01T11:00:00+00:00')), 'second');
        self::assertCount(2, $files->list());
        $files->applyRetention(1);
        self::assertCount(1, $files->list());
        self::assertSame([], (new BackupFileStorage($projectDir.'/empty'))->legacyPaths());

        $source = $projectDir.'/var/backups/db-legacy.sql.gz';
        file_put_contents($source, 'plain');
        self::assertSame(Command::SUCCESS, (new CommandTester(new EncryptExistingBackupsCommand($files, $encryption)))->execute([]));
        self::assertFileDoesNotExist($source);

        $runCommand = new CommandTester(new RunDueBackupsCommand($runBackup, $runDue));
        self::assertSame(Command::SUCCESS, $runCommand->execute([]));
        self::assertStringContainsString('No backup due.', $runCommand->getDisplay());
        self::assertSame(Command::FAILURE, $runCommand->execute(['--force' => true]));

        self::assertFalse((new DatabaseBackupDumper($projectDir, 'sqlite:///%kernel.project_dir%/var/data.db'))->isAvailable());
        try {
            (new DatabaseBackupDumper($projectDir, 'sqlite:///%kernel.project_dir%/var/data.db'))->dump($projectDir.'/var/backups/bad.sql.gz');
            self::fail('Expected invalid database URL.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('DATABASE_URL', $exception->getMessage());
        }
        $missingKeySource = $projectDir.'/var/backups/source.sql.gz';
        file_put_contents($missingKeySource, 'plain');
        $this->expectException(\RuntimeException::class);
        (new BackupEncryptionService($projectDir.'/missing.key'))->encryptFile($missingKeySource, $projectDir.'/missing.enc');
    }

    private function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-admin-backup-tests-'.bin2hex(random_bytes(4));
        mkdir($dir.'/var/backups', 0777, true);

        return $dir;
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function requestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }
}
