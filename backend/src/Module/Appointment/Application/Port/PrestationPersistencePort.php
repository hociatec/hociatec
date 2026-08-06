<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Port;

use App\Module\Appointment\Domain\Entity\Prestation;

interface PrestationPersistencePort
{
    public function save(Prestation $prestation): void;

    public function commit(): void;

    public function delete(Prestation $prestation): void;
}
