<?php

declare(strict_types=1);

namespace App\Module\Rating\Infrastructure\Persistence;

use App\Module\Rating\Application\Port\RatingPersistencePort;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RatingPersistence implements RatingPersistencePort
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
