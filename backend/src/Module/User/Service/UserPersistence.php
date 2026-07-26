<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
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
