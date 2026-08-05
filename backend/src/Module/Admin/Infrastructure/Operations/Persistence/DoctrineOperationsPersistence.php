<?php

declare(strict_types=1);

namespace App\Module\Admin\Infrastructure\Operations\Persistence;

use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOperationsPersistence implements OperationsPersistence
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
