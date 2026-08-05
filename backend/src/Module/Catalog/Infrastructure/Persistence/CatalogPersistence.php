<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Persistence;

use App\Module\Catalog\Application\Port\CatalogPersistencePort;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogPersistence implements CatalogPersistencePort
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }

    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
    }
}
