<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
