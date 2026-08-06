<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Infrastructure\Persistence\PrestationPersistence;
use App\Module\Appointment\Infrastructure\Persistence\WorkingDayConfigurationPersistence;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Infrastructure\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Infrastructure\Repository\AuditEventRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Auth\Infrastructure\Persistence\RefreshTokenPersistence;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartItemRepository;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Catalog\Infrastructure\Repository\StockMovementRepository;
use App\Module\Comment\Domain\Entity\ProductComment;
use App\Module\Comment\Infrastructure\Repository\ProductCommentRepository;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRepository;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Repository\OrderItemRepository;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Order\Infrastructure\Repository\StripeWebhookEventRepository;
use App\Module\Order\Infrastructure\Persistence\OrderPersistence;
use App\Module\Order\Infrastructure\Persistence\StripeWebhookEventPersistence;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Infrastructure\Repository\QuoteItemRepository;
use App\Module\Rating\Infrastructure\Persistence\RatingPersistence;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Infrastructure\Repository\SupportRequestRepository;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Infrastructure\Repository\TrainingRoadmapItemRepository;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use App\Shared\Application\LockMode;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RepositoryAndPersistenceBatchTest extends TestCase
{
    public function testSimpleRepositoriesCanBeInstantiated(): void
    {
        self::assertInstanceOf(AuditChecklistItemRepository::class, $this->repository(AuditChecklistItemRepository::class, AuditChecklistItem::class));
        self::assertInstanceOf(BetaCampaignRepository::class, $this->repository(BetaCampaignRepository::class, BetaCampaign::class));
        self::assertInstanceOf(StockMovementRepository::class, $this->repository(StockMovementRepository::class, StockMovement::class));
        self::assertInstanceOf(ProductCommentRepository::class, $this->repository(ProductCommentRepository::class, ProductComment::class));
        self::assertInstanceOf(EmailCampaignRepository::class, $this->repository(EmailCampaignRepository::class, EmailCampaign::class));
        self::assertInstanceOf(OrderItemRepository::class, $this->repository(OrderItemRepository::class, OrderItem::class));
        self::assertInstanceOf(QuoteItemRepository::class, $this->repository(QuoteItemRepository::class, QuoteItem::class));
        self::assertInstanceOf(SupportRequestRepository::class, $this->repository(SupportRequestRepository::class, SupportRequest::class));
        self::assertInstanceOf(TrainingRoadmapItemRepository::class, $this->repository(TrainingRoadmapItemRepository::class, TrainingRoadmapItem::class));
        self::assertInstanceOf(WorkingDayConfigurationRepository::class, $this->repository(WorkingDayConfigurationRepository::class, WorkingDayConfiguration::class));
        self::assertInstanceOf(TradeInRequestRepository::class, $this->repository(TradeInRequestRepository::class, TradeInRequest::class));
        self::assertInstanceOf(StripeWebhookEventRepository::class, $this->repository(StripeWebhookEventRepository::class, StripeWebhookEvent::class));
        self::assertInstanceOf(RefundRequestRepository::class, $this->repository(RefundRequestRepository::class, RefundRequest::class));
        self::assertInstanceOf(CartItemRepository::class, $this->repository(CartItemRepository::class, CartItem::class));
        self::assertInstanceOf(CartSessionRepository::class, $this->repository(CartSessionRepository::class, CartSession::class));
        self::assertInstanceOf(EmailTemplateRepository::class, $this->repository(EmailTemplateRepository::class, EmailTemplate::class));
        self::assertInstanceOf(NewsArticleViewRepository::class, $this->repository(NewsArticleViewRepository::class, NewsArticleView::class));
        self::assertInstanceOf(PrestationRepository::class, $this->repository(PrestationRepository::class, Prestation::class));
    }

    public function testRepositoriesWithSmallCustomMethodsBehaveAgainstBaseApis(): void
    {
        $workingDayRepository = $this->getMockBuilder(WorkingDayConfigurationRepository::class)
            ->setConstructorArgs([$this->registry(WorkingDayConfiguration::class)])
            ->onlyMethods(['findOneBy', 'createQueryBuilder'])
            ->getMock();
        $configuration = new WorkingDayConfiguration(1, true);
        $workingDayRepository->expects(self::once())->method('findOneBy')->with(['dayOfWeek' => 1])->willReturn($configuration);
        $workingDayRepository->expects(self::once())->method('createQueryBuilder')->with('w')->willReturn($this->queryBuilderReturning([$configuration]));
        self::assertSame($configuration, $workingDayRepository->findOneByDay(1));
        self::assertSame([$configuration], $workingDayRepository->findAllOrdered());

        $auditRequestRepository = $this->getMockBuilder(AuditRequestRepository::class)
            ->setConstructorArgs([$this->registry(\App\Module\Audit\Domain\Entity\AuditRequest::class)])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $auditRequestRepository->expects(self::once())->method('createQueryBuilder')->with('a')->willReturn($this->queryBuilderReturning(['items']));
        self::assertSame(['items'], $auditRequestRepository->findByUser($this->user()));

        $auditEventRepository = $this->getMockBuilder(AuditEventRepository::class)
            ->setConstructorArgs([$this->registry(\App\Module\Audit\Domain\Entity\AuditEvent::class)])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $audit = new \App\Module\Audit\Domain\Entity\AuditRequest('AUD-1', $this->user(), \App\Module\Audit\Domain\Entity\AuditType::SEO, 'https://example.com', null);
        $auditEventRepository->expects(self::once())->method('createQueryBuilder')->with('e')->willReturn($this->queryBuilderReturning(['events']));
        self::assertSame(['events'], $auditEventRepository->findByAudit($audit, 'ASC'));

        $prestationRepository = $this->getMockBuilder(PrestationRepository::class)
            ->setConstructorArgs([$this->registry(Prestation::class)])
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();
        $prestation = new Prestation('Diag', 30, 1000);
        $prestationRepository->expects(self::once())->method('createQueryBuilder')->with('p')->willReturn($this->queryBuilderReturning([$prestation]));
        $prestationRepository->expects(self::once())->method('getEntityManager')->willReturn($this->entityManagerForRemoval($prestation));
        self::assertSame([$prestation], $prestationRepository->findAllOrderedByName());
        $prestationRepository->remove($prestation);

        $emailTemplateRepository = $this->getMockBuilder(EmailTemplateRepository::class)
            ->setConstructorArgs([$this->registry(EmailTemplate::class)])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $template = new EmailTemplate('Welcome', 'welcome', 'account_created', 'Sujet', '<p>Hi</p>');
        $emailTemplateRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls($template, $template);
        self::assertSame($template, $emailTemplateRepository->findOneBySlug('welcome'));
        self::assertSame($template, $emailTemplateRepository->findActiveOneByScenarioKey('account_created'));

    }

    public function testFinalRepositoriesExecuteLookupMethodsAgainstSqlite(): void
    {
        $entityManager = $this->repositoryEntityManager();
        $user = $this->user();
        $entityManager->persist($user);

        $category = new Category('Phones', 'phones');
        $entityManager->persist($category);
        $product = new Product('iPhone', 'iphone', 'SKU-1', 'Desc', 100000, 5, $category);
        $entityManager->persist($product);

        $cart = new CartSession('cart-token');
        $cart->setUser($user);
        $entityManager->persist($cart);
        $cartItem = new CartItem($cart, $product, 2);
        $entityManager->persist($cartItem);

        $article = new NewsArticle('News', 'news', 'Excerpt', 'Content');
        $entityManager->persist($article);
        $viewA = new NewsArticleView($article, 'hash-a');
        $viewB = new NewsArticleView($article, 'hash-b');
        $entityManager->persist($viewA);
        $entityManager->persist($viewB);

        $order = new Order('ORD-1', $user);
        $entityManager->persist($order);
        $refund = new RefundRequest($order, 1200, $user);
        $entityManager->persist($refund);
        $webhook = new StripeWebhookEvent('evt_1', 'checkout.session.completed');
        $entityManager->persist($webhook);
        $entityManager->flush();
        $userId = (int) $user->getId();

        $cartItemRepository = $this->repositoryWithEntityManager(CartItemRepository::class, $entityManager);
        self::assertSame($cartItem->getId(), $cartItemRepository->findOneByCartAndProduct($cart, $product)?->getId());

        $cartSessionRepository = $this->repositoryWithEntityManager(CartSessionRepository::class, $entityManager);
        self::assertSame($cart->getId(), $cartSessionRepository->findOneByToken('cart-token')?->getId());
        self::assertSame($cart->getId(), $cartSessionRepository->findOneByUser($user)?->getId());
        self::assertSame($cart->getId(), $cartSessionRepository->findOneByUserId($userId)?->getId());

        $newsViews = $this->repositoryWithEntityManager(NewsArticleViewRepository::class, $entityManager);
        self::assertSame($viewA, $newsViews->findOneForArticleAndIpHash($article, 'hash-a'));
        self::assertSame(2, $newsViews->countUniqueForArticle($article));

        $refundRepository = $this->repositoryWithEntityManager(RefundRequestRepository::class, $entityManager);
        $entityManager->getConnection()->beginTransaction();
        try {
            self::assertSame($refund->getId(), $refundRepository->findForUpdate((int) $refund->getId())?->getId());
        } finally {
            $entityManager->getConnection()->rollBack();
        }

        $webhookRepository = $this->repositoryWithEntityManager(StripeWebhookEventRepository::class, $entityManager);
        self::assertSame($webhook->getId(), $webhookRepository->findOneByStripeEventId('evt_1')?->getId());
    }

    public function testSmallPersistenceHelpersDelegateToEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(8))->method('persist');
        $entityManager->expects(self::exactly(7))->method('flush');
        $entityManager->expects(self::exactly(2))->method('remove');

        $user = $this->user();
        $order = new Order('ORD-1', $user);

        (new WorkingDayConfigurationPersistence($entityManager))->save(new WorkingDayConfiguration(1, true));
        (new WorkingDayConfigurationPersistence($entityManager))->commit();
        (new RefreshTokenPersistence($entityManager))->save(new RefreshToken($user, 'selector', 'hashed', new \DateTimeImmutable('+1 day')));
        (new RefreshTokenPersistence($entityManager))->commit();
        (new RatingPersistence($entityManager))->persist(new \stdClass());
        (new RatingPersistence($entityManager))->commit();
        (new TradeInPersistence($entityManager))->save($this->tradeInRequest($user));
        (new TradeInPersistence($entityManager))->remove($this->tradeInRequest($user));
        (new OrderPersistence($entityManager))->commit();
        (new OrderPersistence($entityManager))->save($order);
        (new PrestationPersistence($entityManager))->save(new Prestation('Diag', 30, 1000));
        (new PrestationPersistence($entityManager))->commit();
        (new PrestationPersistence($entityManager))->delete(new Prestation('Diag', 30, 1000));
        (new StripeWebhookEventPersistence($entityManager))->save(new StripeWebhookEvent('evt_1', 'checkout.session.completed', '{}'));
        (new StripeWebhookEventPersistence($entityManager))->commit();
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->persist(new \stdClass());
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->commit();
    }

    private function repository(string $repositoryClass, string $entityClass): object
    {
        return new $repositoryClass($this->registry($entityClass));
    }

    private function registry(string $entityClass): ManagerRegistry&MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with($entityClass)->willReturn(new ClassMetadata($entityClass));

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with($entityClass)->willReturn($entityManager);

        return $registry;
    }

    private function queryBuilderReturning(array $result): QueryBuilder&MockObject
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($result);

        $builder = $this->createMock(QueryBuilder::class);
        $builder->method('andWhere')->willReturnSelf();
        $builder->method('setParameter')->willReturnSelf();
        $builder->method('orderBy')->willReturnSelf();
        $builder->method('setFirstResult')->willReturnSelf();
        $builder->method('setMaxResults')->willReturnSelf();
        $builder->method('getQuery')->willReturn($query);

        return $builder;
    }

    private function entityManagerForRemoval(object $entity): EntityManagerInterface&MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($entity);

        return $entityManager;
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function tradeInRequest(User $user): TradeInRequest
    {
        return TradeInRequest::fromLegacySubmittedScalars(
            'TR-1',
            $user,
            'Ada',
            'Lovelace',
            'ada@example.com',
            '0102030405',
            'smartphone',
            'Phone',
            1000,
            2024,
            'Brand',
            'Model',
            'SN',
            'bon',
            true,
            true,
            true,
            'Desc',
            null,
            null,
            100,
            200,
            new \DateTimeImmutable('2026-07-01T10:00:00+00:00'),
        );
    }

    private function repositoryEntityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(CartSession::class),
            $entityManager->getClassMetadata(CartItem::class),
            $entityManager->getClassMetadata(NewsArticle::class),
            $entityManager->getClassMetadata(NewsArticleView::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(RefundRequest::class),
            $entityManager->getClassMetadata(StripeWebhookEvent::class),
        ]);

        return $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     *
     * @return T
     */
    private function repositoryWithEntityManager(string $repositoryClass, EntityManager $entityManager): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new $repositoryClass($registry);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
