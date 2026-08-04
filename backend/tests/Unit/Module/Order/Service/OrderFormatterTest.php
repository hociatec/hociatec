<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Application\Service\OrderFormatter;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use PHPUnit\Framework\TestCase;

final class OrderFormatterTest extends TestCase
{
    public function testItFormatsKnownStatusLabelsAndFallsBackForUnknownValues(): void
    {
        self::assertSame('En attente', OrderFormatter::formatStatusLabel(Order::STATUS_PENDING));
        self::assertSame('Confirmée', OrderFormatter::formatStatusLabel(Order::STATUS_CONFIRMED));
        self::assertSame('Livrée', OrderFormatter::formatStatusLabel(Order::STATUS_DELIVERED));
        self::assertSame('Annulée', OrderFormatter::formatStatusLabel(Order::STATUS_CANCELLED));
        self::assertSame('mystery', OrderFormatter::formatStatusLabel('mystery'));
    }

    public function testItFormatsDeliveryAndInvoiceLabelsAndExposesStatusOptions(): void
    {
        self::assertSame('Préparation en cours', OrderFormatter::formatDeliveryStatusLabel(Order::DELIVERY_STATUS_PREPARING));
        self::assertSame('Expédiée', OrderFormatter::formatDeliveryStatusLabel(Order::DELIVERY_STATUS_SHIPPED));
        self::assertSame('En transit', OrderFormatter::formatDeliveryStatusLabel(Order::DELIVERY_STATUS_IN_TRANSIT));
        self::assertSame('En cours de livraison', OrderFormatter::formatDeliveryStatusLabel(Order::DELIVERY_STATUS_OUT_FOR_DELIVERY));
        self::assertSame('Livrée', OrderFormatter::formatDeliveryStatusLabel(Order::DELIVERY_STATUS_DELIVERED));
        self::assertSame('Incident de livraison', OrderFormatter::formatDeliveryStatusLabel(Order::DELIVERY_STATUS_ISSUE));
        self::assertSame('other', OrderFormatter::formatDeliveryStatusLabel('other'));

        self::assertSame('Émise', OrderFormatter::formatInvoiceStatusLabel(Order::INVOICE_STATUS_ISSUED));
        self::assertSame('Annulée', OrderFormatter::formatInvoiceStatusLabel(Order::INVOICE_STATUS_CANCELLED));
        self::assertSame('draft', OrderFormatter::formatInvoiceStatusLabel('draft'));

        self::assertSame(
            [
                ['value' => Order::STATUS_PENDING, 'label' => 'En attente'],
                ['value' => Order::STATUS_CONFIRMED, 'label' => 'Confirmée'],
                ['value' => Order::STATUS_DELIVERED, 'label' => 'Livrée'],
                ['value' => Order::STATUS_CANCELLED, 'label' => 'Annulée'],
            ],
            OrderFormatter::statusOptions(),
        );

        $workflow = new OrderStatusWorkflow();
        self::assertSame([Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED], $workflow->nextStatuses(Order::STATUS_PENDING));
        self::assertTrue($workflow->canTransitionTo(Order::STATUS_PENDING, Order::STATUS_CONFIRMED));
        self::assertFalse($workflow->canTransitionTo(Order::STATUS_DELIVERED, Order::STATUS_CANCELLED));
    }
}
