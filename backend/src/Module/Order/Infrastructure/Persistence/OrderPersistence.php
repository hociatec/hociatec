<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Persistence;

use App\Module\Order\Application\Port\OrderPersistencePort;
use App\Module\Order\Domain\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderPersistence implements OrderPersistencePort
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
    }
}
