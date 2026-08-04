<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Favorite\Infrastructure\Repository\FavoriteRepository;
use App\Module\Favorite\Application\Service\FavoritePersistence;
use App\Module\Favorite\Application\Service\FavoriteService;
use App\Module\Quote\Application\DTO\QuoteItemAddition;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Application\Service\QuotePersistence;
use App\Module\Quote\Application\Service\QuoteStatusTranslator;
use App\Module\Quote\Application\Service\QuoteWorkflowService;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Application\Service\TradeInFormatter;
use App\Module\TradeIn\Application\Service\TradeInMetadataFormatter;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\Application\Service\TrainingMetadataFormatter;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SmallServicesCoverageTest extends TestCase
{
    public function testFavoriteServiceHandlesAddListRemoveAndExists(): void
    {
        $repository = $this->createMock(FavoriteRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistence = new FavoritePersistence($entityManager);
        $service = new FavoriteService($repository, $persistence);
        $user = $this->user();
        $product = $this->product();
        $existing = new Favorite($user, $product);

        $repository->expects(self::once())->method('findFavoritesForUser')->with($user)->willReturn([$existing]);
        self::assertSame([$existing], $service->listForUser($user));

        $repository->expects(self::once())->method('findOneByUserAndProduct')->with($user, $product)->willReturn($existing);
        $result = $service->addProduct($user, $product);
        self::assertSame($existing, $result['favorite']);
        self::assertFalse($result['created']);

        $repository2 = $this->createMock(FavoriteRepository::class);
        $entityManager2 = $this->createMock(EntityManagerInterface::class);
        $entityManager2->expects(self::once())->method('persist');
        $entityManager2->expects(self::once())->method('flush');
        $service2 = new FavoriteService($repository2, new FavoritePersistence($entityManager2));
        $repository2->method('findOneByUserAndProduct')->willReturn(null);

        $created = $service2->addProduct($user, $product);
        self::assertTrue($created['created']);
        self::assertInstanceOf(Favorite::class, $created['favorite']);

        $repository3 = $this->createMock(FavoriteRepository::class);
        $entityManager3 = $this->createMock(EntityManagerInterface::class);
        $entityManager3->expects(self::once())->method('remove')->with($existing);
        $entityManager3->expects(self::once())->method('flush');
        $repository3->expects(self::exactly(2))->method('findOneByUserAndProduct')->with($user, $product)->willReturnOnConsecutiveCalls($existing, null);
        $repository3->expects(self::once())->method('existsForUserAndProduct')->with($user, $product)->willReturn(true);
        $service3 = new FavoriteService($repository3, new FavoritePersistence($entityManager3));
        $service3->removeProduct($user, $product);
        $service3->removeProduct($user, $product);
        self::assertTrue($service3->isFavorite($user, $product));
    }

    public function testQuotePersistenceTranslatorAndWorkflow(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $quote = new Quote('Q-1');
        $item = new QuoteItem('Item', 1000);
        $persistence = new QuotePersistence($entityManager);

        $entityManager->expects(self::once())->method('persist')->with($quote);
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::once())->method('remove')->with($quote);
        $persistence->save($quote);
        $persistence->flush();
        $persistence->delete($quote);

        $entityManager2 = $this->createMock(EntityManagerInterface::class);
        $entityManager2->expects(self::once())->method('persist')->with($item);
        $entityManager2->expects(self::once())->method('remove')->with($item);
        $persistence2 = new QuotePersistence($entityManager2);
        $persistence2->addItem($quote, $item);
        self::assertSame($quote, $item->getQuote());
        $persistence2->removeItem($item);

        self::assertSame('envoyé', QuoteStatusTranslator::toLabel(Quote::STATUS_SENT));
        self::assertSame(' accepté ', QuoteStatusTranslator::toLabel(' accepté '));
        self::assertSame('  ACCEPTE  ', QuoteStatusTranslator::toLabel('  ACCEPTE  '));
        self::assertSame('custom', QuoteStatusTranslator::toLabel('custom'));
        self::assertSame(Quote::STATUS_ACCEPTED, QuoteStatusTranslator::toCode(Quote::STATUS_ACCEPTED));
        self::assertSame(Quote::STATUS_REFUSED, QuoteStatusTranslator::toCode('Refusé'));
        self::assertSame(Quote::STATUS_EXPIRED, QuoteStatusTranslator::toCode(' EXPIRÉ '));
        self::assertSame('inconnu', QuoteStatusTranslator::toCode(' Inconnu '));
        self::assertSame('', QuoteStatusTranslator::toCode('   '));
        self::assertSame([
            ['value' => Quote::STATUS_DRAFT, 'label' => 'Brouillon'],
            ['value' => Quote::STATUS_SENT, 'label' => 'Envoyé'],
            ['value' => Quote::STATUS_ACCEPTED, 'label' => 'Accepté'],
            ['value' => Quote::STATUS_REFUSED, 'label' => 'Refusé'],
            ['value' => Quote::STATUS_EXPIRED, 'label' => 'Expiré'],
        ], QuoteStatusTranslator::options());
        $this->coverPrivateConstructor(QuoteStatusTranslator::class);

        $entityManager3 = $this->createMock(EntityManagerInterface::class);
        $entityManager3->expects(self::once())->method('remove')->with($quote);
        $entityManager3->expects(self::exactly(4))->method('flush');
        $entityManager3
            ->expects(self::exactly(2))
            ->method('persist')
            ->with(self::callback(static fn (object $entity): bool => $entity instanceof QuoteItem || $entity instanceof Quote));
        $workflow = new QuoteWorkflowService(new QuotePersistence($entityManager3));
        $product = $this->product();
        $this->setId($product, 15);
        $product->setSellingType('rental');

        $workflow->setStatus($quote, Quote::STATUS_SENT);
        self::assertSame(Quote::STATUS_SENT, $quote->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $quote->getCreatedEmailSentAt());

        $input = new QuoteItemAddition(null, null, null, null, 2, 20.0, null, 50);
        $workflow->addProductItem($quote, $product, $input);

        self::assertCount(2, $quote->getItems());
        $items = array_values($quote->getItems()->toArray());
        $addedItem = $items[1];
        self::assertInstanceOf(QuoteItem::class, $addedItem);
        self::assertSame('Phone', $addedItem->getName());
        self::assertSame('jour', $addedItem->getUnit());
        self::assertSame(2000, $addedItem->getVatRateBps());
        self::assertSame(50, $addedItem->getDiscountCents());

        $workflow->save($quote);
        $workflow->delete($quote);
    }

    public function testTradeInMetadataAndFormatterExposeOptionsAndPayload(): void
    {
        $request = new TradeInRequest(
            'TR-1',
            $this->user(),
            'Ada',
            'Lovelace',
            'ada@example.com',
            '0102030405',
            'smartphone',
            'iPhone',
            1000,
            2023,
            'Apple',
            '13',
            'SN',
            'bon',
            true,
            true,
            true,
            'Desc',
            11,
            'iPhone 13',
            200,
            300,
            new \DateTimeImmutable('2026-07-01T10:00:00+00:00'),
        );
        $this->setId($request, 44);
        $request
            ->setStatus(TradeInStatus::UNDER_REVIEW)
            ->setOffer(250, new \DateTimeImmutable('2026-08-01T10:00:00+00:00'))
            ->setClosure(260, 'bank_transfer', 'paid', 'TX-1', new \DateTimeImmutable('2026-07-02T10:00:00+00:00'))
            ->setRib('/tmp/rib.pdf', 'rib.pdf', 100, 'hash')
            ->setReceiptPath('/tmp/receipt.pdf')
            ->setVoucherCode('CODE')
            ->setAdminNote('note');

        $formatter = new TradeInMetadataFormatter();
        self::assertNotEmpty($formatter->categories());
        self::assertNotEmpty($formatter->conditions());
        self::assertNotEmpty($formatter->statuses());
        self::assertNotEmpty($formatter->paymentMethods());
        self::assertNotEmpty($formatter->paymentStatuses());

        $public = TradeInFormatter::format($request, false);
        self::assertArrayNotHasKey('contact', $public);
        self::assertSame('En cours d’étude', $public['statusLabel']);
        self::assertSame('Smartphone', $public['categoryLabel']);
        self::assertSame('Bon état', $public['conditionLabel']);

        $private = TradeInFormatter::format($request, true);
        self::assertSame('Ada', $private['contact']['firstName']);
        self::assertTrue($private['ribAvailable']);
        self::assertTrue($private['receiptAvailable']);
        self::assertSame(['under_review', 'offer_sent', 'cancelled'], $private['allowedNextStatuses']);

        $request->setStatus(TradeInStatus::ACCEPTED);
        self::assertSame(['accepted', 'received', 'cancelled'], TradeInFormatter::format($request, false)['allowedNextStatuses']);

        $request->setStatus(TradeInStatus::COMPLETED);
        $completed = TradeInFormatter::format($request, false);
        self::assertSame(['completed'], $completed['allowedNextStatuses']);
        self::assertSame('completed', TradeInFormatter::conditionLabel('completed'));
        self::assertSame('mystery', TradeInFormatter::categoryLabel('mystery'));
    }

    public function testTrainingMetadataFormatterCachesCategoriesAndFormats(): void
    {
        $repository = $this->createMock(TrainingCategoryRepository::class);
        $category = new TrainingCategory('Infra', 'infra');
        $this->setId($category, 8);
        $repository->expects(self::once())->method('findOrdered')->willReturn([$category]);

        $formatter = new TrainingMetadataFormatter($repository);

        self::assertSame(['id' => 8, 'name' => 'Infra', 'slug' => 'infra'], $formatter->category('infra'));
        self::assertNull($formatter->category('missing'));
        self::assertSame([
            ['value' => 'onsite', 'label' => 'Présentiel'],
            ['value' => 'remote', 'label' => 'Distanciel'],
            ['value' => 'custom', 'label' => 'custom'],
        ], $formatter->formats(['onsite', '', 'remote', 42, 'custom']));
        self::assertSame('Présentiel', $formatter->formatLabel('onsite'));
        self::assertSame('Distanciel', $formatter->formatLabel('remote'));
        self::assertSame('hybrid', $formatter->formatLabel('hybrid'));
        self::assertSame('Payée', $formatter->enrollmentStatusLabel('paid'));
        self::assertSame('unknown', $formatter->enrollmentStatusLabel('unknown'));
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function product(): Product
    {
        return new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, new Category('Phones', 'phones'));
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private function coverPrivateConstructor(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $constructor->invoke($reflection->newInstanceWithoutConstructor());
    }
}
