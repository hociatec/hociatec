<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderWorkflowService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function cancel(Order $order): void
    {
        $order
            ->setStatus(Order::STATUS_CANCELLED)
            ->setInvoiceStatus(Order::INVOICE_STATUS_CANCELLED);

        $this->entityManager->flush();
    }
}
