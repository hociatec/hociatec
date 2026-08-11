<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Projection\OrderItemFormatter;
use App\Module\Order\Application\Projection\OrderStatusLabelFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use PHPUnit\Framework\TestCase;

final class OrderFormatterTest extends TestCase
{
    public function testItFormatsKnownStatusLabelsAndFallsBackForUnknownValues(): void
    {
        self::assertSame('En attente', $this->formatter()->formatStatusLabel(Order::STATUS_PENDING));
        self::assertSame('Confirmée', $this->formatter()->formatStatusLabel(Order::STATUS_CONFIRMED));
        self::assertSame('Livrée', $this->formatter()->formatStatusLabel(Order::STATUS_DELIVERED));
        self::assertSame('Annulée', $this->formatter()->formatStatusLabel(Order::STATUS_CANCELLED));
        self::assertSame('mystery', $this->formatter()->formatStatusLabel('mystery'));
    }

    public function testItFormatsDeliveryAndInvoiceLabelsAndExposesStatusOptions(): void
    {
        self::assertSame('Préparation en cours', $this->formatter()->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_PREPARING));
        self::assertSame('Expédiée', $this->formatter()->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_SHIPPED));
        self::assertSame('En transit', $this->formatter()->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_IN_TRANSIT));
        self::assertSame('En cours de livraison', $this->formatter()->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_OUT_FOR_DELIVERY));
        self::assertSame('Livrée', $this->formatter()->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_DELIVERED));
        self::assertSame('Incident de livraison', $this->formatter()->formatDeliveryStatusLabel(Order::DELIVERY_STATUS_ISSUE));
        self::assertSame('other', $this->formatter()->formatDeliveryStatusLabel('other'));

        self::assertSame('Émise', $this->formatter()->formatInvoiceStatusLabel(Order::INVOICE_STATUS_ISSUED));
        self::assertSame('Annulée', $this->formatter()->formatInvoiceStatusLabel(Order::INVOICE_STATUS_CANCELLED));
        self::assertSame('draft', $this->formatter()->formatInvoiceStatusLabel('draft'));

        self::assertSame(
            [
                ['value' => Order::STATUS_PENDING, 'label' => 'En attente'],
                ['value' => Order::STATUS_CONFIRMED, 'label' => 'Confirmée'],
                ['value' => Order::STATUS_DELIVERED, 'label' => 'Livrée'],
                ['value' => Order::STATUS_CANCELLED, 'label' => 'Annulée'],
            ],
            $this->formatter()->statusOptions(),
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

    private function formatter(): OrderFormatter
    {
        return new OrderFormatter(
            new OrderStatusLabelFormatter(),
            new OrderItemFormatter(new ProductReviewFormatter()),
            new OrderStatusWorkflow(),
        );
    }
}
