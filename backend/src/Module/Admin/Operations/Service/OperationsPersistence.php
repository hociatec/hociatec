<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Shared\Application\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;

/** Persistence boundary for operational workflows. */
final readonly class OperationsPersistence implements TransactionManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
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
