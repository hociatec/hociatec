<?php

declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence;

use App\Module\User\Application\Port\UserPersistencePort;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserPersistence implements UserPersistencePort
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
}
