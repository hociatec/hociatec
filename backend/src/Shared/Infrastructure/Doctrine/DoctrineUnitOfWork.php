<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

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

    public function queryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder();
    }

    public function clear(): void
    {
        $this->entityManager->clear();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function findForUpdate(string $class, int $id): ?object
    {
        $entity = $this->entityManager->find($class, $id, LockMode::PESSIMISTIC_WRITE);

        return $entity;
    }
}
