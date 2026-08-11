<?php

declare(strict_types=1);

namespace App\Module\Catalog\Infrastructure\Persistence;

use App\Module\Catalog\Application\Port\CatalogPersistencePort;
use App\Shared\Application\UnitOfWork;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogPersistence implements CatalogPersistencePort, UnitOfWork
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function persist(object $entity): void
    {
        $this->save($entity);
    }

    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
    }

    public function remove(object $entity): void
    {
        $this->delete($entity);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
