<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Upload;

use App\Shared\Infrastructure\Transaction\InMemoryTransactionSideEffectRegistry;
use App\Shared\Infrastructure\Upload\VichUploadTransactionCompensationSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class VichUploadTransactionCompensationSubscriberTest extends TestCase
{
    public function testSubscribedEventsAndPrivatePathHelpersAreCovered(): void
    {
        self::assertSame([
            \Vich\UploaderBundle\Event\Events::PRE_REMOVE => 'backupBeforeRemove',
            \Vich\UploaderBundle\Event\Events::POST_UPLOAD => 'removeUploadedFileOnRollback',
        ], VichUploadTransactionCompensationSubscriber::getSubscribedEvents());

        $directory = $this->temporaryDirectory();
        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, new NullLogger());
        $reflection = new \ReflectionObject($subscriber);

        $pathFor = $reflection->getMethod('pathFor');
        $pathFor->setAccessible(true);
        $restoreBackupIfMissing = $reflection->getMethod('restoreBackupIfMissing');
        $restoreBackupIfMissing->setAccessible(true);

        $object = new class {
            public ?string $imageName = 'asset.jpg';
        };
        $mapping = $this->mapping($directory, 'nested');
        self::assertSame($directory.'/nested/asset.jpg', $pathFor->invoke($subscriber, $object, $mapping));

        $emptyObject = new class {
            public ?string $imageName = '';
        };
        self::assertNull($pathFor->invoke($subscriber, $emptyObject, $mapping));

        $existing = $directory.'/existing.jpg';
        file_put_contents($existing, 'current');
        $backup = $directory.'/backup.jpg';
        file_put_contents($backup, 'backup');
        $restoreBackupIfMissing->invoke($subscriber, $backup, $existing);
        self::assertSame('current', file_get_contents($existing));

        unlink($existing);
        $restoreBackupIfMissing->invoke($subscriber, $backup, $existing);
        self::assertSame('backup', file_get_contents($existing));
    }

    public function testUploadedFileIsRemovedWhenDatabaseTransactionRollsBack(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory.'/new-image.jpg';
        file_put_contents($path, 'new');

        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, new NullLogger());
        $object = new class {
            public ?string $imageName = 'new-image.jpg';
        };

        $registry->begin();
        $subscriber->removeUploadedFileOnRollback(new Event($object, $this->mapping($directory)));
        $registry->rollback();

        self::assertFileDoesNotExist($path);
    }

    public function testRemovedFileIsRestoredWhenDatabaseTransactionRollsBack(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory.'/old-image.jpg';
        file_put_contents($path, 'old');

        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, new NullLogger());
        $object = new class {
            public ?string $imageName = 'old-image.jpg';
        };

        $registry->begin();
        $subscriber->backupBeforeRemove(new Event($object, $this->mapping($directory)));
        unlink($path);
        $registry->rollback();

        self::assertFileExists($path);
        self::assertSame('old', file_get_contents($path));
    }

    public function testBackupLifecycleHandlesCommitNoTrackingAndMissingPaths(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory.'/commit-image.jpg';
        file_put_contents($path, 'commit');

        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $logger = new CollectingWarningLogger();
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, $logger);
        $object = new class {
            public ?string $imageName = 'commit-image.jpg';
        };

        $subscriber->backupBeforeRemove(new Event($object, $this->mapping($directory)));
        $subscriber->removeUploadedFileOnRollback(new Event($object, $this->mapping($directory)));
        self::assertFileExists($path);

        $registry->begin();
        $subscriber->backupBeforeRemove(new Event($object, $this->mapping($directory)));
        unlink($path);
        $registry->commit();
        self::assertFileDoesNotExist($path);

        $emptyObject = new class {
            public ?string $imageName = null;
        };
        $registry->begin();
        $subscriber->backupBeforeRemove(new Event($emptyObject, $this->mapping($directory)));
        $subscriber->removeUploadedFileOnRollback(new Event($emptyObject, $this->mapping($directory)));
        $registry->rollback();

        self::assertSame([], $logger->warnings);
    }

    public function testRollbackLogsWarningWhenUploadedFileIsRemoved(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory.'/created-image.jpg';
        file_put_contents($path, 'new');

        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $logger = new CollectingWarningLogger();
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, $logger);
        $object = new class {
            public ?string $imageName = 'created-image.jpg';
        };

        $registry->begin();
        $subscriber->removeUploadedFileOnRollback(new Event($object, $this->mapping($directory)));
        $registry->rollback();

        self::assertFileDoesNotExist($path);
        self::assertCount(1, $logger->warnings);
        self::assertSame('Removed upload created before database rollback.', $logger->warnings[0]['message']);
    }

    private function mapping(string $directory, string $uploadDir = ''): PropertyMapping
    {
        $mapping = new PropertyMapping('imageFile', 'imageName');
        $mapping->setMappingName('product_images');
        $mapping->setMapping([
            'upload_destination' => $directory,
            'uri_prefix' => '/uploads/products',
            'upload_dir' => $uploadDir,
        ]);

        return $mapping;
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/hociatec-upload-test-'.bin2hex(random_bytes(8));
        mkdir($directory);

        return $directory;
    }
}

final class CollectingWarningLogger extends AbstractLogger
{
    /** @var list<array{level:string,message:string,context:array<string,mixed>}> */
    public array $warnings = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if ('warning' !== $level) {
            return;
        }

        $this->warnings[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
