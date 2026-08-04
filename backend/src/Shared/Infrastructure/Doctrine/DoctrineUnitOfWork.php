<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

/** Infrastructure boundary for Doctrine unit-of-work operations. */
final readonly class DoctrineUnitOfWork
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->entityManager->remove($entity);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
