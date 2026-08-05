<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\EmailCampaignRecipientRepositoryPort;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\User\Domain\Entity\User;
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

    public function findOneForCampaignAndUser(EmailCampaign $campaign, User $user): ?EmailCampaignRecipient
    {
        return $this->findOneBy(['campaign' => $campaign, 'user' => $user]);
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
}
