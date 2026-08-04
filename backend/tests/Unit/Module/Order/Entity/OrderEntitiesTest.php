<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Entity;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class OrderEntitiesTest extends TestCase
{
    public function testOrderMutatorsInvoiceFieldsAndItems(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $order = new Order('ORD-1', $user);
        $originalUpdatedAt = $order->getUpdatedAt();

        $item = new OrderItem('Phone', 'PH-1', 10000, 2);
        $item
            ->setVatRateBps(-20)
            ->setLineSubtotalCents(-1)
            ->setLineVatCents(-1)
            ->setLineTotalCents(-1);

        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $item->setProduct($product);

        $order
            ->setNumber('ORD-2')
            ->setUser($user)
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setTotalPriceCents(-100)
            ->setSubtotalPriceCents(-200)
            ->setDiscountAmountCents(-300)
            ->setLoyaltyPointsAwarded(-5)
            ->setAppliedPromotionName('Promo')
            ->setAppliedPromotionSlug('promo')
            ->setShippingName('Ada')
            ->setShippingAddress('1 rue')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setDeliveryStatus(Order::DELIVERY_STATUS_SHIPPED)
            ->setDeliveryCarrier('DHL')
            ->setDeliveryTrackingNumber('TRK')
            ->setDeliveryTrackingUrl('https://track')
            ->setDeliveryEstimatedAt(new \DateTimeImmutable('+1 day'))
            ->setDeliveryShippedAt(new \DateTimeImmutable('now'))
            ->setDeliveryDeliveredAt(new \DateTimeImmutable('+2 day'))
            ->setInvoiceNumber('INV-1')
            ->setInvoiceStatus(Order::INVOICE_STATUS_CANCELLED)
            ->setInvoicedAt(new \DateTimeImmutable('now'))
            ->setBillingName('Ada')
            ->setBillingCompany('OpenAI')
            ->setBillingCompanySiren('123')
            ->setBillingCompanyVatNumber('FR123')
            ->setPurchaseOrderNumber('PO-1')
            ->setBillingEmail('ada@example.com')
            ->setBillingAddress('1 rue')
            ->setBillingPostalCode('75001')
            ->setBillingCity('Paris')
            ->setCurrencyCode('USD')
            ->setElectronicFormat('FACTUR-X')
            ->setInvoicePdfPath('/tmp/a.pdf')
            ->setInvoiceXmlPath('/tmp/a.xml')
            ->setOrderCreatedEmailSentAt(new \DateTimeImmutable('now'))
            ->setInvoiceEmailSentAt(new \DateTimeImmutable('now'))
            ->setStatusConfirmedEmailSentAt(new \DateTimeImmutable('now'))
            ->setStatusDeliveredEmailSentAt(new \DateTimeImmutable('now'))
            ->setStatusCancelledEmailSentAt(new \DateTimeImmutable('now'))
            ->addItem($item);

        self::assertSame('ORD-2', $order->getNumber());
        self::assertSame($user, $order->getUser());
        self::assertSame(Order::STATUS_CONFIRMED, $order->getStatus());
        self::assertSame(0, $order->getTotalPriceCents());
        self::assertSame(0, $order->getSubtotalPriceCents());
        self::assertSame(0, $order->getDiscountAmountCents());
        self::assertSame(0, $order->getLoyaltyPointsAwarded());
        self::assertSame('Promo', $order->getAppliedPromotionName());
        self::assertSame('promo', $order->getAppliedPromotionSlug());
        self::assertSame('Ada', $order->getShippingName());
        self::assertSame('1 rue', $order->getShippingAddress());
        self::assertSame(Order::DELIVERY_STATUS_SHIPPED, $order->getDeliveryStatus());
        self::assertSame('DHL', $order->getDeliveryCarrier());
        self::assertSame('INV-1', $order->getInvoiceNumber());
        self::assertSame(Order::INVOICE_STATUS_CANCELLED, $order->getInvoiceStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getInvoicedAt());
        self::assertSame('Ada', $order->getBillingName());
        self::assertSame('OpenAI', $order->getBillingCompany());
        self::assertSame('123', $order->getBillingCompanySiren());
        self::assertSame('FR123', $order->getBillingCompanyVatNumber());
        self::assertSame('PO-1', $order->getPurchaseOrderNumber());
        self::assertSame('ada@example.com', $order->getBillingEmail());
        self::assertSame('1 rue', $order->getBillingAddress());
        self::assertSame('75001', $order->getBillingPostalCode());
        self::assertSame('Paris', $order->getBillingCity());
        self::assertSame('USD', $order->getCurrencyCode());
        self::assertSame('FACTUR-X', $order->getElectronicFormat());
        self::assertSame('/tmp/a.pdf', $order->getInvoicePdfPath());
        self::assertSame('/tmp/a.xml', $order->getInvoiceXmlPath());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getOrderCreatedEmailSentAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getInvoiceEmailSentAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getStatusConfirmedEmailSentAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getStatusDeliveredEmailSentAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $order->getStatusCancelledEmailSentAt());
        self::assertSame($order, $item->getOrder());
        self::assertSame($product, $item->getProduct());
        self::assertSame(0, $item->getVatRateBps());
        self::assertSame(0, $item->getLineSubtotalCents());
        self::assertSame(0, $item->getLineVatCents());
        self::assertSame(0, $item->getLineTotalCents());
        self::assertSame(20000, $item->getLinePriceCents());

        $item->setLineTotalCents(22000);
        self::assertSame(22000, $item->getLinePriceCents());

        $order->removeItem($item);
        self::assertCount(0, $order->getItems());
        self::assertNull($item->getOrder());

        usleep(1000);
        $order->touch();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $order->getUpdatedAt());
    }

    public function testOrderEventExposesConstructorValues(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $order = new Order('ORD-1', $user);
        $event = new OrderEvent($order, 'status_changed', 'Done', 42, 'Admin');

        self::assertNull($event->getId());
        self::assertSame($order, $event->getOrder());
        self::assertSame('status_changed', $event->getType());
        self::assertSame('Done', $event->getMessage());
        self::assertSame(42, $event->getActorUserId());
        self::assertSame('Admin', $event->getActorName());
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());
    }
}
