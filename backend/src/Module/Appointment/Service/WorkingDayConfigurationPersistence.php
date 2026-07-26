<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\WorkingDayConfiguration;
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

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
