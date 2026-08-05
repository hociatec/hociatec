<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Port;

use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;

interface WorkingDayConfigurationRepositoryPort
{
    public function findOneByDay(int $dayOfWeek): ?WorkingDayConfiguration;

    public function findOneByDayForUpdate(int $dayOfWeek): ?WorkingDayConfiguration;

    /** @return list<WorkingDayConfiguration> */
    public function findAllOrdered(): array;
}
