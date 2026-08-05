<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Repository;

use App\Module\Order\Application\Port\StripeWebhookEventRepositoryPort;

use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<StripeWebhookEvent> */
final class StripeWebhookEventRepository extends ServiceEntityRepository implements StripeWebhookEventRepositoryPort
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
