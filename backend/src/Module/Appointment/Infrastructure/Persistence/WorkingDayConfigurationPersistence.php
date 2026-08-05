<?php

declare(strict_types=1);

namespace App\Module\Appointment\Infrastructure\Persistence;

use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WorkingDayConfigurationPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(WorkingDayConfiguration $configuration): void
    {
        $this->entityManager->persist($configuration);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
