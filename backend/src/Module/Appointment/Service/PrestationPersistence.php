<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\Prestation;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PrestationPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Prestation $prestation): void
    {
        $this->entityManager->persist($prestation);
        $this->entityManager->flush();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function delete(Prestation $prestation): void
    {
        $this->entityManager->remove($prestation);
        $this->entityManager->flush();
    }
}
