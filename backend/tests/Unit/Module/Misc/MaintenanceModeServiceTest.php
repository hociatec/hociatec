<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Backup\Service\MaintenanceModeService;
use PHPUnit\Framework\TestCase;

final class MaintenanceModeServiceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/hociatec-maintenance-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        if (is_file($this->projectDir.'/var/maintenance.json')) {
            unlink($this->projectDir.'/var/maintenance.json');
        }
        if (is_dir($this->projectDir.'/var')) {
            rmdir($this->projectDir.'/var');
        }
        if (is_dir($this->projectDir)) {
            rmdir($this->projectDir);
        }
    }

    public function testServiceReadsDefaultsWritesAndReadsBackState(): void
    {
        $service = new MaintenanceModeService($this->projectDir);

        self::assertSame([
            'enabled' => false,
            'message' => 'Le site est temporairement en maintenance. Merci de revenir dans quelques minutes.',
            'updatedAt' => null,
        ], $service->getStatus());
        self::assertFalse($service->isEnabled());

        $status = $service->set(true, '  Intervention planifiée  ');
        self::assertTrue($status['enabled']);
        self::assertSame('Intervention planifiée', $status['message']);
        self::assertNotNull($status['updatedAt']);
        self::assertTrue($service->isEnabled());
        self::assertSame('Intervention planifiée', $service->getStatus()['message']);

        file_put_contents($this->projectDir.'/var/maintenance.json', "{bad");
        self::assertSame([
            'enabled' => false,
            'message' => 'Le site est temporairement en maintenance. Merci de revenir dans quelques minutes.',
            'updatedAt' => null,
        ], $service->getStatus());
    }

    public function testServiceNormalizesBlankMessageAndInvalidUpdatedAtFromFile(): void
    {
        $service = new MaintenanceModeService($this->projectDir);

        $status = $service->set(false, '   ');
        self::assertFalse($status['enabled']);
        self::assertSame(
            'Le site est temporairement en maintenance. Merci de revenir dans quelques minutes.',
            $status['message'],
        );

        mkdir($this->projectDir.'/var', 0775, true);
        file_put_contents($this->projectDir.'/var/maintenance.json', json_encode([
            'enabled' => true,
            'message' => '  ',
            'updatedAt' => 123,
        ], JSON_THROW_ON_ERROR));

        self::assertSame([
            'enabled' => true,
            'message' => 'Le site est temporairement en maintenance. Merci de revenir dans quelques minutes.',
            'updatedAt' => null,
        ], $service->getStatus());

        file_put_contents($this->projectDir.'/var/maintenance.json', " \n ");
        self::assertSame([
            'enabled' => false,
            'message' => 'Le site est temporairement en maintenance. Merci de revenir dans quelques minutes.',
            'updatedAt' => null,
        ], $service->getStatus());
    }

    public function testServiceThrowsWhenMaintenanceDirectoryCannotBePrepared(): void
    {
        mkdir($this->projectDir, 0775, true);
        file_put_contents($this->projectDir.'/var', 'occupied');

        $service = new MaintenanceModeService($this->projectDir);

        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Impossible de préparer le dossier de maintenance.');
            $service->set(true, 'Message');
        } finally {
            restore_error_handler();
        }
    }

    public function testServiceThrowsWhenMaintenanceStateCannotBeSaved(): void
    {
        mkdir($this->projectDir.'/var/maintenance.json', 0775, true);
        $service = new MaintenanceModeService($this->projectDir);

        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Impossible de sauvegarder le mode maintenance.');
            $service->set(true, 'Message');
        } finally {
            restore_error_handler();
        }
    }
}
