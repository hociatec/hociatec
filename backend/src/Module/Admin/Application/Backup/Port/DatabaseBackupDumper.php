<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Backup\Port;

interface DatabaseBackupDumper
{
    public function dump(string $targetPath): void;

    public function isAvailable(): bool;
}
