<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\OrderEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderEventPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(OrderEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
