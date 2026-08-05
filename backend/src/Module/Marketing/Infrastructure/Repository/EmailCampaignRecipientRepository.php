<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\EmailCampaignRecipientRepositoryPort;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailCampaignRecipient>
 */
final class EmailCampaignRecipientRepository extends ServiceEntityRepository implements EmailCampaignRecipientRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailCampaignRecipient::class);
    }

    public function findOneForCampaignAndUserIds(int $campaignId, int $userId): ?EmailCampaignRecipient
    {
        return $this->createQueryBuilder('recipient')
            ->andWhere('IDENTITY(recipient.campaign) = :campaignId')
            ->andWhere('IDENTITY(recipient.user) = :userId')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExistingUserIdsForCampaign(int $campaignId, array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }

        /** @var list<array{userId:int|string}> $rows */
        $rows = $this->createQueryBuilder('recipient')
            ->select('IDENTITY(recipient.user) AS userId')
            ->andWhere('IDENTITY(recipient.campaign) = :campaignId')
            ->andWhere('IDENTITY(recipient.user) IN (:userIds)')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('userIds', array_values(array_unique($userIds)))
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['userId'], $rows);
    }
}
