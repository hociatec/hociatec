<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Entity;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class QuoteEntitiesTest extends TestCase
{
    public function testQuoteAndQuoteItemMutators(): void
    {
        $quote = new Quote('Q-1');
        $quoteUpdatedAt = $quote->getUpdatedAt();
        $item = new QuoteItem('Service item', 15000);
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $order = new Order('ORD-1', $user);

        $quote
            ->setNumber('Q-2')
            ->setStatus(Quote::STATUS_SENT)
            ->setCustomerName('Ada')
            ->setCustomerEmail('ada@example.com')
            ->setCustomerCompany('OpenAI')
            ->setCustomerAddress('1 rue')
            ->setGlobalDiscountCents(-100)
            ->setShippingCents(-200)
            ->setConditions('Net 30')
            ->setValidFrom(new \DateTimeImmutable('2026-07-01'))
            ->setValidUntil(new \DateTimeImmutable('2026-08-01'))
            ->setCreatedEmailSentAt(new \DateTimeImmutable('2026-07-02T09:00:00+00:00'))
            ->setConvertedOrder($order)
            ->addItem($item);

        self::assertNull($quote->getId());
        self::assertSame('Q-2', $quote->getNumber());
        self::assertSame(Quote::STATUS_SENT, $quote->getStatus());
        self::assertSame('Ada', $quote->getCustomerName());
        self::assertSame('ada@example.com', $quote->getCustomerEmail());
        self::assertSame('OpenAI', $quote->getCustomerCompany());
        self::assertSame('1 rue', $quote->getCustomerAddress());
        self::assertSame(0, $quote->getGlobalDiscountCents());
        self::assertSame(0, $quote->getShippingCents());
        self::assertSame('Net 30', $quote->getConditions());
        self::assertSame('2026-07-01', $quote->getValidFrom()?->format('Y-m-d'));
        self::assertSame('2026-08-01', $quote->getValidUntil()?->format('Y-m-d'));
        self::assertSame('2026-07-02T09:00:00+00:00', $quote->getCreatedEmailSentAt()?->format(DATE_ATOM));
        self::assertSame($order, $quote->getConvertedOrder());
        self::assertInstanceOf(\DateTimeImmutable::class, $quote->getCreatedAt());
        self::assertCount(1, $quote->getItems());
        self::assertSame($quote, $item->getQuote());

        $item
            ->setItemType(QuoteItem::TYPE_PRODUCT)
            ->setProductId(11)
            ->setServiceId(22)
            ->setName('Produit')
            ->setDescription('Desc')
            ->setUnit('pcs')
            ->setQuantity(-5)
            ->setUnitPriceCents(-10)
            ->setVatRateBps(-20)
            ->setDiscountCents(-30);

        self::assertNull($item->getId());
        self::assertSame(QuoteItem::TYPE_PRODUCT, $item->getItemType());
        self::assertSame(11, $item->getProductId());
        self::assertSame(22, $item->getServiceId());
        self::assertSame('Produit', $item->getName());
        self::assertSame('Desc', $item->getDescription());
        self::assertSame('pcs', $item->getUnit());
        self::assertSame(1, $item->getQuantity());
        self::assertSame(0, $item->getUnitPriceCents());
        self::assertSame(0, $item->getVatRateBps());
        self::assertSame(0, $item->getDiscountCents());

        $quote->removeItem($item);
        self::assertCount(0, $quote->getItems());
        self::assertNull($item->getQuote());

        usleep(1000);
        $quote->touch();
        self::assertGreaterThanOrEqual($quoteUpdatedAt, $quote->getUpdatedAt());
    }

    public function testServiceMutatorsAndTouch(): void
    {
        $service = new Service('Audit', 12000, 2000);
        $updatedAt = $service->getUpdatedAt();

        $service
            ->setTitle('Audit 2')
            ->setDescription('Desc')
            ->setUnit('hour')
            ->setDurationValue(3)
            ->setDurationUnit('day')
            ->setPriceCents(15000)
            ->setVatRateBps(2100);

        self::assertNull($service->getId());
        self::assertSame('Audit 2', $service->getTitle());
        self::assertSame('Desc', $service->getDescription());
        self::assertSame('hour', $service->getUnit());
        self::assertSame(3, $service->getDurationValue());
        self::assertSame('day', $service->getDurationUnit());
        self::assertSame(15000, $service->getPriceCents());
        self::assertSame(2100, $service->getVatRateBps());
        self::assertInstanceOf(\DateTimeImmutable::class, $service->getCreatedAt());

        usleep(1000);
        $service->touch();
        self::assertGreaterThanOrEqual($updatedAt, $service->getUpdatedAt());
    }
}
