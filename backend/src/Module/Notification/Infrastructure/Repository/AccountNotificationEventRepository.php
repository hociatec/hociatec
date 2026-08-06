<?php

declare(strict_types=1);

namespace App\Module\Notification\Infrastructure\Repository;

use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountNotificationEvent>
 */
final class AccountNotificationEventRepository extends ServiceEntityRepository implements AccountNotificationEventRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountNotificationEvent::class);
    }

    /**
     * @return list<AccountNotificationEvent>
     */
    public function findRecentForUser(User $user, int $limit = 30, int $offset = 0): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC'], $limit, $offset);
    }

    public function countForUser(User $user): int
    {
        return $this->count(['user' => $user]);
    }

    public function existsForKey(string $key): bool
    {
        return null !== $this->findOneBy(['key' => $key]);
    }
}
