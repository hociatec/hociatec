<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Service;

use Doctrine\ORM\EntityManagerInterface;

/** Persistence boundary for operational workflows. */
final readonly class OperationsPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
