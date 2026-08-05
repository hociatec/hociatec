<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Persistence;

use App\Module\Order\Application\Port\StripeWebhookEventPersistencePort;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class StripeWebhookEventPersistence implements StripeWebhookEventPersistencePort
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(StripeWebhookEvent $event): void
    {
        $this->entityManager->persist($event);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
