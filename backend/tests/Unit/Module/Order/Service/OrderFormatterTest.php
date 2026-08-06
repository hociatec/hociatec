<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use PHPUnit\Framework\TestCase;

final class OrderFormatterTest extends TestCase
{
    public function testItFormatsKnownStatusLabelsAndFallsBackForUnknownValues(): void
    {
        self::assertSame('En attente', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatStatusLabel(Order::STATUS_PENDING));
        self::assertSame('Confirmée', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatStatusLabel(Order::STATUS_CONFIRMED));
        self::assertSame('Livrée', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatStatusLabel(Order::STATUS_DELIVERED));
        self::assertSame('Annulée', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatStatusLabel(Order::STATUS_CANCELLED));
        self::assertSame('mystery', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatStatusLabel('mystery'));
    }

    public function testItFormatsDeliveryAndInvoiceLabelsAndExposesStatusOptions(): void
    {
        self::assertSame('Préparation en cours', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_PREPARING));
        self::assertSame('Expédiée', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_SHIPPED));
        self::assertSame('En transit', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_IN_TRANSIT));
        self::assertSame('En cours de livraison', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_OUT_FOR_DELIVERY));
        self::assertSame('Livrée', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_DELIVERED));
        self::assertSame('Incident de livraison', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_ISSUE));
        self::assertSame('other', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatDeliveryStatusLabel('other'));

        self::assertSame('Émise', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatInvoiceStatusLabel(Order::INVOICE_STATUS_ISSUED));
        self::assertSame('Annulée', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatInvoiceStatusLabel(Order::INVOICE_STATUS_CANCELLED));
        self::assertSame('draft', (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->formatInvoiceStatusLabel('draft'));

        self::assertSame(
            [
                ['value' => Order::STATUS_PENDING, 'label' => 'En attente'],
                ['value' => Order::STATUS_CONFIRMED, 'label' => 'Confirmée'],
                ['value' => Order::STATUS_DELIVERED, 'label' => 'Livrée'],
                ['value' => Order::STATUS_CANCELLED, 'label' => 'Annulée'],
            ],
            (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new OrderStatusWorkflow()))->statusOptions(),
        );

        $workflow = new OrderStatusWorkflow();
        self::assertSame([Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED], $workflow->nextStatuses(Order::STATUS_PENDING));
        self::assertTrue($workflow->canTransitionTo(Order::STATUS_PENDING, Order::STATUS_CONFIRMED));
        self::assertSame('confirm', $workflow->transitionFor(Order::STATUS_PENDING, Order::STATUS_CONFIRMED));
        self::assertSame('cancel', $workflow->transitionFor(Order::STATUS_PENDING, Order::STATUS_CANCELLED));
        self::assertSame('deliver', $workflow->transitionFor(Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED));
        self::assertNull($workflow->transitionFor(Order::STATUS_DELIVERED, Order::STATUS_CONFIRMED));
        self::assertFalse($workflow->canTransitionTo(Order::STATUS_DELIVERED, Order::STATUS_CANCELLED));
    }
}
