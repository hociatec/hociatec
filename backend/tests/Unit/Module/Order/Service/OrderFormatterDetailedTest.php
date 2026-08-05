<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Comment\Domain\Entity\ProductComment;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class OrderFormatterDetailedTest extends TestCase
{
    public function testFormatOrderBuildsFullPayloadWithReviewAndPromotion(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setEntityId($user, 7);

        $order = new Order('ORD-100', $user);
        $this->setEntityId($order, 99);
        $order
            ->setStatus(Order::STATUS_DELIVERED)
            ->setSubtotalPriceCents(30000)
            ->setDiscountAmountCents(5000)
            ->setTotalPriceCents(25000)
            ->setAppliedPromotionName('Promo ete')
            ->setAppliedPromotionSlug('promo-ete')
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('1 rue')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setDeliveryStatus(Order::DELIVERY_STATUS_DELIVERED)
            ->setDeliveryCarrier('DHL')
            ->setDeliveryTrackingNumber('TRACK-1')
            ->setDeliveryTrackingUrl('https://track')
            ->setDeliveryEstimatedAt(new \DateTimeImmutable('+1 day'))
            ->setDeliveryShippedAt(new \DateTimeImmutable('-1 day'))
            ->setDeliveryDeliveredAt(new \DateTimeImmutable('now'))
            ->setInvoiceNumber('INV-100')
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable('now'))
            ->setBillingName('Ada Lovelace')
            ->setBillingCompany('OpenAI')
            ->setBillingCompanySiren('123')
            ->setBillingCompanyVatNumber('FR123')
            ->setPurchaseOrderNumber('PO-123')
            ->setBillingEmail('ada@example.com')
            ->setBillingAddress('1 rue')
            ->setBillingPostalCode('75001')
            ->setBillingCity('Paris')
            ->setCurrencyCode('EUR')
            ->setElectronicFormat('UBL-2.1');

        $category = new Category('Phones', 'phones');
        $productA = new Product('Phone A', 'phone-a', 'PHA', 'Desc', 10000, 5, $category);
        $productB = new Product('Phone B', 'phone-b', 'PHB', 'Desc', 20000, 5, $category);
        $this->setEntityId($productA, 11);
        $this->setEntityId($productB, 12);

        $itemWithReview = new OrderItem('Phone A', 'PHA', 10000, 1);
        $this->setEntityId($itemWithReview, 101);
        $itemWithReview
            ->setProduct($productA)
            ->setVatRateBps(2000)
            ->setLineSubtotalCents(8333)
            ->setLineVatCents(1667)
            ->setLineTotalCents(10000);

        $itemPendingReview = new OrderItem('Phone B', 'PHB', 10000, 2);
        $this->setEntityId($itemPendingReview, 102);
        $itemPendingReview
            ->setProduct($productB)
            ->setVatRateBps(2000)
            ->setLineSubtotalCents(16667)
            ->setLineVatCents(3333)
            ->setLineTotalCents(20000);

        $order->addItem($itemWithReview)->addItem($itemPendingReview);

        $rating = new ProductRating($productA, $itemWithReview, $user, 5);
        $this->setEntityId($rating, 501);
        $rating->publish();
        $comment = new ProductComment($rating, 'Excellent');
        $rating->setComment($comment);

        $formatted = (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()))->formatOrder(
            $order,
            [$itemWithReview->getId() => $rating],
            ['source' => 'test']
        );

        self::assertSame(99, $formatted['id']);
        self::assertSame('ORD-100', $formatted['number']);
        self::assertSame(7, $formatted['userId']);
        self::assertSame('Ada Lovelace', $formatted['customerDisplayName']);
        self::assertSame(Order::STATUS_DELIVERED, $formatted['status']);
        self::assertSame('Livrée', $formatted['statusLabel']);
        self::assertSame([], $formatted['allowedNextStatuses']);
        self::assertSame([], $formatted['allowedNextStatusDetails']);
        self::assertSame(30000, $formatted['subtotalPriceCents']);
        self::assertSame(5000, $formatted['discountAmountCents']);
        self::assertSame(25000, $formatted['totalPriceCents']);
        self::assertSame(['name' => 'Promo ete', 'slug' => 'promo-ete'], $formatted['appliedPromotion']);
        self::assertSame(1, $formatted['pendingReviewsCount']);
        self::assertTrue($formatted['hasPendingReviews']);
        self::assertSame('Ada Lovelace', $formatted['shipping']['name']);
        self::assertSame(Order::DELIVERY_STATUS_DELIVERED, $formatted['delivery']['status']);
        self::assertSame('Livrée', $formatted['delivery']['statusLabel']);
        self::assertSame('INV-100', $formatted['invoice']['number']);
        self::assertSame('Émise', $formatted['invoice']['statusLabel']);
        self::assertSame('test', $formatted['source']);
        self::assertCount(2, $formatted['items']);

        self::assertFalse($formatted['items'][0]['canReview']);
        self::assertSame(501, $formatted['items'][0]['review']['id']);
        self::assertSame('Excellent', $formatted['items'][0]['review']['comment']);
        self::assertSame('Ada L.', $formatted['items'][0]['review']['author']['displayName']);
        self::assertSame(101, $formatted['items'][0]['review']['orderItemId']);

        self::assertTrue($formatted['items'][1]['canReview']);
        self::assertNull($formatted['items'][1]['review']);
        self::assertSame(12, $formatted['items'][1]['productId']);
    }

    public function testFormatOrderIgnoresQuoteConversionPromotionNameAndHandlesAnonymousReviewName(): void
    {
        $user = new User('client@example.com', 'Client', '', new \DateTimeImmutable('1990-01-01'), '0102030405', 'other');
        $this->setEntityId($user, 9);

        $order = new Order('ORD-200', $user);
        $this->setEntityId($order, 200);
        $order
            ->setStatus(Order::STATUS_PENDING)
            ->setAppliedPromotionName('Conversion devis 123')
            ->setAppliedPromotionSlug('should-hide');

        $item = new OrderItem('Service', 'SRV', 15000, 1);
        $this->setEntityId($item, 201);
        $order->addItem($item);

        $formatted = (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()))->formatOrder($order);

        self::assertNull($formatted['appliedPromotion']);
        self::assertSame([Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED], $formatted['allowedNextStatuses']);
        self::assertSame([
            ['value' => Order::STATUS_CONFIRMED, 'label' => 'Confirmée'],
            ['value' => Order::STATUS_CANCELLED, 'label' => 'Annulée'],
        ], $formatted['allowedNextStatusDetails']);
        self::assertFalse($formatted['hasPendingReviews']);
        self::assertSame('Client', $formatted['customerDisplayName']);
    }

    public function testFormatOrderExposesConfirmedNextStatusAndNullProductData(): void
    {
        $user = new User('ops@example.com', 'Ops', 'Team', new \DateTimeImmutable('1990-01-01'), '0102030405', 'other');
        $this->setEntityId($user, 10);

        $order = new Order('ORD-300', $user);
        $this->setEntityId($order, 300);
        $order->setStatus(Order::STATUS_CONFIRMED);

        $item = new OrderItem('Service only', 'SRV', 5000, 1);
        $this->setEntityId($item, 301);
        $order->addItem($item);

        $formatted = (new OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow()))->formatOrder($order);

        self::assertSame([Order::STATUS_DELIVERED], $formatted['allowedNextStatuses']);
        self::assertSame([
            ['value' => Order::STATUS_DELIVERED, 'label' => 'Livrée'],
        ], $formatted['allowedNextStatusDetails']);
        self::assertNull($formatted['items'][0]['productId']);
        self::assertFalse($formatted['items'][0]['canReview']);
        self::assertNull($formatted['items'][0]['review']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
