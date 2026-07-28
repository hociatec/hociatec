<?php

declare(strict_types=1);

namespace App\Module\Notification\Repository;

use App\Module\Notification\Entity\AccountNotificationEvent;
use App\Module\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountNotificationEvent>
 */
final class AccountNotificationEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountNotificationEvent::class);
    }

    /**
     * @return list<AccountNotificationEvent>
     */
    public function findRecentForUser(User $user, int $limit = 30): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC'], $limit);
    }

    public function existsForKey(string $key): bool
    {
        return null !== $this->findOneBy(['key' => $key]);
    }
}
