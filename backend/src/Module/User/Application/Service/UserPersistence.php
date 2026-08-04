<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Infrastructure\Application\TransactionManager;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserPersistence implements TransactionManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
    }

    public function remove(User $user): void
    {
        $this->entityManager->remove($user);
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
