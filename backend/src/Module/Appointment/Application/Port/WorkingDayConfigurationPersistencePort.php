<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Port;

use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;

interface WorkingDayConfigurationPersistencePort
{
    public function save(WorkingDayConfiguration $configuration): void;
    public function commit(): void;
}
