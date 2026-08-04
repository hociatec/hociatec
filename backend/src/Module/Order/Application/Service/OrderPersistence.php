<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Order\Domain\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
    }
}
