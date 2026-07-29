<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Service;

use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Quote\DTO\QuoteItemPayload;
use App\Module\Quote\DTO\QuotePayload;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuoteNumberGenerator;
use App\Module\Quote\Service\QuotePersistence;
use App\Module\Quote\Service\QuoteService;
use App\Shared\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class QuoteServiceTest extends TestCase
{
    public function testCreateEmptyPersistsDraftQuoteWithGeneratedNumber(): void
    {
        $service = $this->service(number: 'DEV-2026-0001');

        $quote = $service->createEmpty();

        self::assertSame('DEV-2026-0001', $quote->getNumber());
        self::assertSame(Quote::STATUS_DRAFT, $quote->getStatus());
        self::assertCount(0, $quote->getItems());
    }

    public function testCreateFromPayloadHydratesDefaultsResolvedProductAndTotals(): void
    {
        $product = $this->rentalProduct();
        $service = $this->service(number: 'DEV-2026-0002', product: $product);

        $payload = new QuotePayload(
            ['name' => ' Ada ', 'email' => ' ada@example.com ', 'company' => ' Hociatec ', 'address' => ' Paris '],
            ' Envoyé ',
            Money::fromCents(500),
            Money::fromCents(1500),
            null,
            null,
            null,
            [
                new QuoteItemPayload('', 11, null, null, ' Ligne produit ', null, 2, 2000, 100, null),
                new QuoteItemPayload('', null, 5, null, null, null, 0, 0, 0, null),
            ],
        );

        $quote = $service->createFromPayload($payload);
        $totals = $service->computeTotals($quote);

        self::assertSame('Ada', $quote->getCustomerName());
        self::assertSame('ada@example.com', $quote->getCustomerEmail());
        self::assertSame('Hociatec', $quote->getCustomerCompany());
        self::assertSame('Paris', $quote->getCustomerAddress());
        self::assertSame(Quote::STATUS_SENT, $quote->getStatus());
        self::assertSame(500, $quote->getGlobalDiscountCents());
        self::assertSame(1500, $quote->getShippingCents());
        self::assertSame(QuoteService::DEFAULT_CONDITIONS, $quote->getConditions());
        self::assertSame('2026-07-29', $quote->getValidFrom()?->format('Y-m-d'));
        self::assertSame('2026-08-28', $quote->getValidUntil()?->format('Y-m-d'));
        self::assertCount(2, $quote->getItems());

        $productItem = $quote->getItems()->first();
        self::assertInstanceOf(QuoteItem::class, $productItem);
        self::assertSame(QuoteItem::TYPE_PRODUCT, $productItem->getItemType());
        self::assertSame('Produit location', $productItem->getName());
        self::assertSame(4200, $productItem->getUnitPriceCents());
        self::assertSame('jour', $productItem->getUnit());
        self::assertSame('Ligne produit', $productItem->getDescription());

        $fallbackItem = $quote->getItems()->last();
        self::assertInstanceOf(QuoteItem::class, $fallbackItem);
        self::assertSame('Ligne', $fallbackItem->getName());
        self::assertSame(0, $fallbackItem->getUnitPriceCents());
        self::assertSame(1, $fallbackItem->getQuantity());

        self::assertSame(['totalHt' => 7800, 'totalVat' => 1660, 'totalTtc' => 10960], $totals);
    }

    public function testUpdateFromPayloadClearsItemsAppliesExplicitDatesAndPersistsFlushOnly(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistence = new QuotePersistence($entityManager);
        $productRepository = $this->createMock(ProductRepository::class);
        $numberGenerator = $this->numberGenerator('DEV-2026-9999');
        $service = new QuoteService($persistence, $productRepository, $numberGenerator, new QuoteCalculator());

        $quote = new Quote('DEV-2026-0003');
        $existing = new QuoteItem('Ancienne ligne', 1000);
        $quote->addItem($existing);
        $quote->setValidFrom(new \DateTimeImmutable('2026-07-01'))
            ->setValidUntil(new \DateTimeImmutable('2026-07-31'))
            ->setConditions('Anciennes conditions');

        $entityManager->expects(self::once())->method('remove')->with($existing);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(QuoteItem::class));
        $entityManager->expects(self::once())->method('flush');

        $updated = $service->updateFromPayload(
            $quote,
            new QuotePayload(
                ['name' => 'Client'],
                '   ',
                Money::fromCents(0),
                Money::fromCents(0),
                ' Conditions mises à jour ',
                '2026-08-01',
                '2026-08-15',
                [new QuoteItemPayload('Service', null, null, 5000, ' Desc ', ' heure ', 2, 2000, 300, QuoteItem::TYPE_SERVICE)],
            ),
        );

        self::assertSame($quote, $updated);
        self::assertSame(Quote::STATUS_DRAFT, $quote->getStatus());
        self::assertSame('Conditions mises à jour', $quote->getConditions());
        self::assertSame('2026-08-01', $quote->getValidFrom()?->format('Y-m-d'));
        self::assertSame('2026-08-15', $quote->getValidUntil()?->format('Y-m-d'));
        self::assertCount(1, $quote->getItems());

        $item = $quote->getItems()->first();
        self::assertInstanceOf(QuoteItem::class, $item);
        self::assertSame(QuoteItem::TYPE_SERVICE, $item->getItemType());
        self::assertSame('Desc', $item->getDescription());
        self::assertSame('heure', $item->getUnit());
        self::assertSame(2, $item->getQuantity());
        self::assertSame(300, $item->getDiscountCents());
    }

    public function testDuplicateCopiesQuoteFieldsAndItemsThenDeleteDelegates(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistence = new QuotePersistence($entityManager);
        $service = new QuoteService(
            $persistence,
            $this->createMock(ProductRepository::class),
            $this->numberGenerator('DEV-2026-0004'),
            new QuoteCalculator(),
        );

        $source = new Quote('DEV-2026-0003');
        $source->setStatus(Quote::STATUS_ACCEPTED)
            ->setCustomerName('Ada')
            ->setCustomerEmail('ada@example.com')
            ->setCustomerCompany('Hociatec')
            ->setCustomerAddress('Paris')
            ->setGlobalDiscountCents(700)
            ->setShippingCents(300)
            ->setConditions('Conditions')
            ->setValidFrom(new \DateTimeImmutable('2026-07-10'))
            ->setValidUntil(new \DateTimeImmutable('2026-08-10'));
        $sourceItem = (new QuoteItem('Audit', 12000))
            ->setItemType(QuoteItem::TYPE_SERVICE)
            ->setProductId(5)
            ->setServiceId(8)
            ->setDescription('Description')
            ->setUnit('jour')
            ->setQuantity(3)
            ->setVatRateBps(2000)
            ->setDiscountCents(500);
        $source->addItem($sourceItem);

        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Quote::class));
        $entityManager->expects(self::exactly(2))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(Quote::class));

        $copy = $service->duplicate($source);
        $service->delete($copy);

        self::assertSame('DEV-2026-0004', $copy->getNumber());
        self::assertSame(Quote::STATUS_DRAFT, $copy->getStatus());
        self::assertSame('Ada', $copy->getCustomerName());
        self::assertSame('2026-08-10', $copy->getValidUntil()?->format('Y-m-d'));
        self::assertCount(1, $copy->getItems());

        $copiedItem = $copy->getItems()->first();
        self::assertInstanceOf(QuoteItem::class, $copiedItem);
        self::assertNotSame($sourceItem, $copiedItem);
        self::assertSame(QuoteItem::TYPE_SERVICE, $copiedItem->getItemType());
        self::assertSame(5, $copiedItem->getProductId());
        self::assertSame(8, $copiedItem->getServiceId());
        self::assertSame('Description', $copiedItem->getDescription());
        self::assertSame('jour', $copiedItem->getUnit());
        self::assertSame(3, $copiedItem->getQuantity());
    }

    public function testStringAndDateHelpersNormalizeAndRejectInvalidDate(): void
    {
        self::assertNull(QuoteService::strOrNull(null));
        self::assertNull(QuoteService::strOrNull('   '));
        self::assertSame('Ada', QuoteService::strOrNull(' Ada '));
        self::assertNull(QuoteService::dateOrNull('   '));
        self::assertSame('2026-07-29T00:00:00+00:00', QuoteService::dateOrNull('2026-07-29')?->format(DATE_ATOM));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format de date invalide. Utilisez YYYY-MM-DD.');
        QuoteService::dateOrNull('2026/07/29');
    }

    private function service(string $number, ?Product $product = null): QuoteService
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::atLeastOnce())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('find')->willReturn($product);

        return new QuoteService(
            new QuotePersistence($entityManager),
            $productRepository,
            $this->numberGenerator($number),
            new QuoteCalculator(),
        );
    }

    private function numberGenerator(string $number): QuoteNumberGenerator
    {
        $generator = $this->getMockBuilder(QuoteNumberGenerator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generate'])
            ->getMock();
        $generator->method('generate')->willReturn($number);

        return $generator;
    }

    private function rentalProduct(): Product
    {
        $category = new Category('Laptops', 'laptops');
        $product = new Product('Produit location', 'produit-location', 'SKU-LOC', 'Desc', 4200, 12, $category);
        $product->setSellingType('rental');
        $this->setId($product, 11);

        return $product;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
