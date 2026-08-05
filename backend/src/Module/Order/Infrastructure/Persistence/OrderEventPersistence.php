<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Persistence;

use App\Module\Order\Application\Port\OrderEventPersistencePort;
use App\Module\Order\Domain\Entity\OrderEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderEventPersistence implements OrderEventPersistencePort
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(OrderEvent $event): void
    {
        $this->entityManager->persist($event);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
