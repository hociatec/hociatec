<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class StripeWebhookEventPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(StripeWebhookEvent $event): void
    {
        $this->entityManager->persist($event);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
