<?php

declare(strict_types=1);

namespace App\Module\Appointment\Infrastructure\Persistence;

use App\Module\Appointment\Application\Port\PrestationPersistencePort;
use App\Module\Appointment\Domain\Entity\Prestation;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PrestationPersistence implements PrestationPersistencePort
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Prestation $prestation): void
    {
        $this->entityManager->persist($prestation);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function delete(Prestation $prestation): void
    {
        $this->entityManager->remove($prestation);
    }
}
