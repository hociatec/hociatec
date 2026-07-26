<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/** Infrastructure boundary for unit-of-work operations. */
final readonly class DoctrinePersistence
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

    public function flush(): void
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

    /**
     * @template T
     *
     * @param \Closure(): T $operation
     *
     * @return T
     */
    public function transactional(\Closure $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(static fn (): mixed => $operation());
    }
}
