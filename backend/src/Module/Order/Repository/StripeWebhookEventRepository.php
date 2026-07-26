<?php

declare(strict_types=1);

namespace App\Module\Order\Repository;

use App\Module\Order\Entity\StripeWebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<StripeWebhookEvent> */
final class StripeWebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StripeWebhookEvent::class);
    }

    public function findOneByStripeEventId(string $eventId): ?StripeWebhookEvent
    {
        return $this->findOneBy(['stripeEventId' => $eventId]);
    }
}
