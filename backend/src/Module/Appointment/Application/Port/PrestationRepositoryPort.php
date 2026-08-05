<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Port;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Shared\Application\LockMode;

interface PrestationRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Prestation;

    /** @return list<Prestation> */
    public function findAllOrderedByName(): array;

    public function remove(Prestation $prestation): void;
}
