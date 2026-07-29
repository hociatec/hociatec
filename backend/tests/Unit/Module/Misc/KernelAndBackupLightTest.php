<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Kernel;
use App\Module\Admin\Backup\Controller\SystemStatusController;
use App\Module\Admin\Backup\Service\MaintenanceModeService;
use PHPUnit\Framework\TestCase;

final class KernelAndBackupLightTest extends TestCase
{
    public function testKernelBootAndSystemStatusController(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        self::assertNotNull($kernel->getContainer());
        $kernel->shutdown();

        $directory = sys_get_temp_dir().'/hociatec-maintenance-'.bin2hex(random_bytes(4));
        mkdir($directory, 0777, true);
        $service = new MaintenanceModeService($directory);

        $payload = json_decode((string) (new SystemStatusController($service))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['data']['maintenance']['enabled']);

        @rmdir($directory);
    }
}
