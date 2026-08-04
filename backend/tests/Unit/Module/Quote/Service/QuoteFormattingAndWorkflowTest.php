<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Service;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Quote\Application\DTO\QuoteItemAddition;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Domain\Entity\Service as QuoteServiceEntity;
use App\Module\Quote\Application\Service\QuoteFormatter;
use App\Module\Quote\Application\Service\QuotePersistence;
use App\Module\Quote\Application\Service\QuoteWorkflowService;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class QuoteFormattingAndWorkflowTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testFormatServiceBuildsDurationLabelAndVatRate(): void
    {
        $service = new QuoteServiceEntity('Audit sécurité', 25000, 2000);
        $this->setId($service, 14);
        $service
            ->setDescription('Audit complet')
            ->setUnit('jour')
            ->setDurationValue(2)
            ->setDurationUnit('day');

        $payload = QuoteFormatter::formatService($service);

        self::assertSame(14, $payload['id']);
        self::assertSame('Audit sécurité', $payload['title']);
        self::assertSame('Audit complet', $payload['description']);
        self::assertSame('jour', $payload['unit']);
        self::assertSame(2, $payload['durationValue']);
        self::assertSame('day', $payload['durationUnit']);
        self::assertSame('2 jours', $payload['durationLabel']);
        self::assertSame(25000, $payload['priceCents']);
        self::assertSame(20, $payload['vatRate']);
    }

    public function testFormatServiceOmitsDurationLabelWhenDurationDataIsIncomplete(): void
    {
        $service = new QuoteServiceEntity('Hotline', 5000, 550);
        $payload = QuoteFormatter::formatService($service);

        self::assertNull($payload['durationValue']);
        self::assertNull($payload['durationUnit']);
        self::assertNull($payload['durationLabel']);
        self::assertSame(5.5, $payload['vatRate']);
    }

    public function testFormatQuoteIncludesItemsTotalsCustomerDatesAndConvertedOrder(): void
    {
        $quote = (new Quote('Q-2026-050'))
            ->setStatus(Quote::STATUS_SENT)
            ->setCustomerName('Ada Lovelace')
            ->setCustomerEmail('ada@example.com')
            ->setCustomerCompany('Hociatec')
            ->setCustomerAddress('1 rue de Paris')
            ->setGlobalDiscountCents(1000)
            ->setShippingCents(490)
            ->setConditions('Paiement 30 jours')
            ->setValidFrom(new \DateTimeImmutable('2026-07-01'))
            ->setValidUntil(new \DateTimeImmutable('2026-08-01'))
            ->setCreatedEmailSentAt(new \DateTimeImmutable('2026-07-02T10:00:00+00:00'));
        $this->setId($quote, 50);

        $quoteItem = (new QuoteItem('Licence', 10000))
            ->setItemType(QuoteItem::TYPE_PRODUCT)
            ->setProductId(9)
            ->setServiceId(3)
            ->setDescription('Abonnement annuel')
            ->setUnit('poste')
            ->setQuantity(2)
            ->setVatRateBps(2000)
            ->setDiscountCents(500);
        $this->setId($quoteItem, 501);
        $quote->addItem($quoteItem);

        $order = $this->createOrder();
        $quote->setConvertedOrder($order);

        $payload = QuoteFormatter::formatQuote($quote, new \App\Module\Quote\Application\Service\QuoteCalculator());

        self::assertSame(50, $payload['id']);
        self::assertSame('Q-2026-050', $payload['number']);
        self::assertSame(Quote::STATUS_SENT, $payload['statusCode']);
        self::assertSame('envoyé', $payload['status']);
        self::assertSame('envoyé', $payload['statusLabel']);
        self::assertSame('Ada Lovelace', $payload['customer']['name']);
        self::assertSame('ada@example.com', $payload['customer']['email']);
        self::assertSame('Hociatec', $payload['customer']['company']);
        self::assertSame('1 rue de Paris', $payload['customer']['address']);
        self::assertSame(1000, $payload['discountCents']);
        self::assertSame(490, $payload['shippingCents']);
        self::assertSame('Paiement 30 jours', $payload['conditions']);
        self::assertSame('2026-07-01', $payload['validFrom']);
        self::assertSame('2026-08-01', $payload['validUntil']);
        self::assertSame('2026-07-02T10:00:00+00:00', $payload['sentAt']);
        self::assertCount(1, $payload['items']);
        self::assertSame(501, $payload['items'][0]['id']);
        self::assertSame(18500, $payload['totals']['ht']);
        self::assertSame(3900, $payload['totals']['vat']);
        self::assertSame(22890, $payload['totals']['ttc']);
        self::assertSame('ORD-2026-001', $payload['convertedOrder']['number']);
        self::assertSame('pending', $payload['convertedOrder']['status']);
    }

    public function testWorkflowSetStatusKeepsOriginalSentTimestampAfterFirstSend(): void
    {
        $quote = new Quote('Q-2026-051');
        $sentAt = new \DateTimeImmutable('2026-07-04T10:30:00+00:00');
        $quote->setCreatedEmailSentAt($sentAt);

        $this->entityManager->expects(self::exactly(2))->method('flush');

        $workflow = new QuoteWorkflowService(new QuotePersistence($this->entityManager));
        $workflow->setStatus($quote, Quote::STATUS_SENT);
        $workflow->setStatus($quote, Quote::STATUS_ACCEPTED);

        self::assertSame(Quote::STATUS_ACCEPTED, $quote->getStatus());
        self::assertSame($sentAt, $quote->getCreatedEmailSentAt());
    }

    public function testWorkflowAddProductItemUsesOverridesAndNormalizedOptionalValues(): void
    {
        $quote = new Quote('Q-2026-052');
        $product = $this->createProduct();

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $entity): bool {
                self::assertInstanceOf(QuoteItem::class, $entity);
                /** @var QuoteItem $entity */
                self::assertSame('Audit mensuel', $entity->getName());
                self::assertSame(12000, $entity->getUnitPriceCents());
                self::assertSame('Pack support', $entity->getDescription());
                self::assertSame('mois', $entity->getUnit());
                self::assertSame(3, $entity->getQuantity());
                self::assertSame(2100, $entity->getVatRateBps());
                self::assertSame(700, $entity->getDiscountCents());

                return true;
            }));
        $this->entityManager->expects(self::once())->method('flush');

        $workflow = new QuoteWorkflowService(new QuotePersistence($this->entityManager));
        $workflow->addProductItem($quote, $product, QuoteItemAddition::fromArray([
            'name' => '  Audit mensuel  ',
            'unitPriceCents' => 12000,
            'description' => '  Pack support  ',
            'unit' => '  mois  ',
            'quantity' => 3,
            'vatRateBps' => 2100,
            'discountCents' => 700,
        ]));

        self::assertCount(1, $quote->getItems());
        $item = $quote->getItems()->first();
        self::assertInstanceOf(QuoteItem::class, $item);
        self::assertSame($quote, $item->getQuote());
        self::assertSame(91, $item->getProductId());
    }

    public function testWorkflowAddProductItemFallsBackToRentalDefaultsAndFloatVatRate(): void
    {
        $quote = new Quote('Q-2026-053');
        $product = $this->createProduct();
        $product->setSellingType('rental');

        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(QuoteItem::class));
        $this->entityManager->expects(self::once())->method('flush');

        $workflow = new QuoteWorkflowService(new QuotePersistence($this->entityManager));
        $workflow->addProductItem($quote, $product, new QuoteItemAddition(null, null, ' ', null, 2, 5.5, null, null));

        $item = $quote->getItems()->first();
        self::assertInstanceOf(QuoteItem::class, $item);
        self::assertSame('Phone', $item->getName());
        self::assertSame(9900, $item->getUnitPriceCents());
        self::assertNull($item->getDescription());
        self::assertSame('jour', $item->getUnit());
        self::assertSame(550, $item->getVatRateBps());
        self::assertSame(0, $item->getDiscountCents());
    }

    private function createOrder(): Order
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 42);
        $order = new Order('ORD-2026-001', $user);
        $this->setId($order, 77);
        $order
            ->setSubtotalPriceCents(10000)
            ->setDiscountAmountCents(500)
            ->setTotalPriceCents(9500)
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('1 rue de Paris')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris');

        $item = (new OrderItem('Licence', 'SKU-1', 10000, 1))
            ->setLineSubtotalCents(10000)
            ->setLineVatCents(2000)
            ->setLineTotalCents(12000);
        $this->setId($item, 701);
        $order->addItem($item);

        return $order;
    }

    private function createProduct(): Product
    {
        $category = new Category('Téléphones', 'telephones');
        $product = new Product('Phone', 'phone', 'SKU-PHONE', 'Desc', 9900, 10, $category);
        $this->setId($product, 91);

        return $product;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
