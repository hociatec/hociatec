<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Order\Infrastructure\Repository\StripeWebhookEventRepository;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class RepositorySqliteIntegrationTest extends RepositoryTestCase
{
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

    public function testSqliteSchemaEnforcesUniqueWebhookEventIds(): void
    {
        $entityManager = $this->repositoryEntityManager();
        $entityManager->persist(new StripeWebhookEvent('evt_unique', 'checkout.session.completed'));
        $entityManager->flush();

        $entityManager->persist(new StripeWebhookEvent('evt_unique', 'checkout.session.completed'));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaExposesRepresentativeIndexesAndForeignKeys(): void
    {
        $entityManager = $this->integrityEntityManager();
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        $outboxIndexes = array_change_key_case($schemaManager->listTableIndexes('outbox_events'), \CASE_LOWER);
        self::assertArrayHasKey('idx_outbox_pending', $outboxIndexes);
        self::assertSame(['status', 'available_at', 'created_at'], $outboxIndexes['idx_outbox_pending']->getColumns());

        $refreshIndexes = array_change_key_case($schemaManager->listTableIndexes('auth_refresh_tokens'), \CASE_LOWER);
        self::assertArrayHasKey('idx_auth_refresh_tokens_expires_at', $refreshIndexes);
        self::assertArrayHasKey('primary', $refreshIndexes);

        $notificationIndexes = array_change_key_case($schemaManager->listTableIndexes('account_notification_events'), \CASE_LOWER);
        self::assertArrayHasKey('idx_account_notification_user', $notificationIndexes);
        self::assertArrayHasKey('uniq_account_notification_key', $notificationIndexes);

        $articleIndexes = array_change_key_case($schemaManager->listTableIndexes('news_articles'), \CASE_LOWER);
        self::assertArrayHasKey('idx_news_articles_published', $articleIndexes);
        self::assertSame(['is_published', 'published_at'], $articleIndexes['idx_news_articles_published']->getColumns());

        $commentIndexes = array_change_key_case($schemaManager->listTableIndexes('news_comments'), \CASE_LOWER);
        self::assertArrayHasKey('idx_news_comments_article', $commentIndexes);
        self::assertSame(['article_id', 'created_at'], $commentIndexes['idx_news_comments_article']->getColumns());

        $productIndexes = array_change_key_case($schemaManager->listTableIndexes('catalog_products'), \CASE_LOWER);
        self::assertArrayHasKey('idx_catalog_products_publication', $productIndexes);
        self::assertSame(['is_published', 'is_featured_home', 'created_at'], $productIndexes['idx_catalog_products_publication']->getColumns());
        self::assertArrayHasKey('idx_catalog_products_category_publication', $productIndexes);
        self::assertSame(['category_id', 'is_published', 'created_at'], $productIndexes['idx_catalog_products_category_publication']->getColumns());
        self::assertArrayHasKey('idx_catalog_products_price_publication', $productIndexes);
        self::assertSame(['is_published', 'price_cents'], $productIndexes['idx_catalog_products_price_publication']->getColumns());

        $orderIndexes = array_change_key_case($schemaManager->listTableIndexes('orders'), \CASE_LOWER);
        self::assertArrayHasKey('idx_orders_status_created', $orderIndexes);
        self::assertSame(['status', 'created_at'], $orderIndexes['idx_orders_status_created']->getColumns());
        self::assertArrayHasKey('idx_orders_user_created', $orderIndexes);
        self::assertSame(['user_id', 'created_at'], $orderIndexes['idx_orders_user_created']->getColumns());
        self::assertArrayHasKey('idx_orders_invoiced_at', $orderIndexes);
        self::assertSame(['invoiced_at'], $orderIndexes['idx_orders_invoiced_at']->getColumns());

        $checkoutIndexes = array_change_key_case($schemaManager->listTableIndexes('order_checkout_sessions'), \CASE_LOWER);
        self::assertArrayHasKey('idx_checkout_user_cart_status', $checkoutIndexes);
        self::assertSame(['user_id', 'cart_token', 'status'], $checkoutIndexes['idx_checkout_user_cart_status']->getColumns());
        self::assertArrayHasKey('idx_checkout_user_order_status', $checkoutIndexes);
        self::assertSame(['user_id', 'order_id', 'status'], $checkoutIndexes['idx_checkout_user_order_status']->getColumns());
        self::assertArrayHasKey('idx_checkout_status_created', $checkoutIndexes);
        self::assertSame(['status', 'created_at'], $checkoutIndexes['idx_checkout_status_created']->getColumns());
        self::assertArrayHasKey('idx_checkout_customer_email', $checkoutIndexes);
        self::assertSame(['customer_email'], $checkoutIndexes['idx_checkout_customer_email']->getColumns());

        $tradeInIndexes = array_change_key_case($schemaManager->listTableIndexes('trade_in_requests'), \CASE_LOWER);
        self::assertArrayHasKey('idx_trade_in_status_created', $tradeInIndexes);
        self::assertSame(['status', 'created_at'], $tradeInIndexes['idx_trade_in_status_created']->getColumns());
        self::assertArrayHasKey('idx_trade_in_requester_created', $tradeInIndexes);
        self::assertSame(['requester_user_id', 'created_at'], $tradeInIndexes['idx_trade_in_requester_created']->getColumns());
        self::assertArrayHasKey('idx_trade_in_email', $tradeInIndexes);
        self::assertSame(['email'], $tradeInIndexes['idx_trade_in_email']->getColumns());
        self::assertArrayHasKey('idx_trade_in_closed_at', $tradeInIndexes);
        self::assertSame(['closed_at'], $tradeInIndexes['idx_trade_in_closed_at']->getColumns());

        $enrollmentIndexes = array_change_key_case($schemaManager->listTableIndexes('training_enrollments'), \CASE_LOWER);
        foreach ([
            'idx_training_enrollment_session',
            'idx_training_enrollment_user',
            'idx_training_enrollment_stripe_session',
            'idx_training_enrollment_slot',
        ] as $indexName) {
            self::assertArrayHasKey($indexName, $enrollmentIndexes);
        }

        $sessionIndexes = array_change_key_case($schemaManager->listTableIndexes('training_sessions'), \CASE_LOWER);
        self::assertArrayHasKey('idx_training_session_training', $sessionIndexes);
        self::assertArrayHasKey('idx_training_session_starts', $sessionIndexes);

        $enrollmentColumns = array_change_key_case($schemaManager->listTableColumns('training_enrollments'), \CASE_LOWER);
        self::assertTrue($enrollmentColumns['session_id']->getNotnull());
        self::assertTrue($enrollmentColumns['user_id']->getNotnull());

        $foreignKeys = $schemaManager->listTableForeignKeys('training_enrollments');
        $foreignKeyMap = [];
        foreach ($foreignKeys as $foreignKey) {
            $foreignKeyMap[implode(',', $foreignKey->getLocalColumns())] = $foreignKey->getForeignTableName();
        }

        self::assertSame('training_sessions', $foreignKeyMap['session_id'] ?? null);
        self::assertSame('users', $foreignKeyMap['user_id'] ?? null);
    }

    public function testSqliteSchemaEnforcesUniqueUserEmails(): void
    {
        $entityManager = $this->integrityEntityManager();
        $entityManager->persist($this->user());
        $entityManager->flush();

        $entityManager->persist($this->user());

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueOutboxEventKeys(): void
    {
        $entityManager = $this->integrityEntityManager();
        $entityManager->persist(new OutboxEvent('duplicate-key', 'test.event', ['id' => 1]));
        $entityManager->flush();

        $entityManager->persist(new OutboxEvent('duplicate-key', 'test.event', ['id' => 2]));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueTrainingEnrollmentPerSessionAndUser(): void
    {
        $entityManager = $this->integrityEntityManager();
        [$session, $user] = $this->trainingGraph($entityManager);

        $entityManager->persist(new TrainingEnrollment($session, $user, 1500));
        $entityManager->flush();

        $entityManager->persist(new TrainingEnrollment($session, $user, 1500));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueOrderNumbers(): void
    {
        $entityManager = $this->integrityEntityManager();
        $user = $this->user();
        $entityManager->persist($user);
        $entityManager->flush();

        $entityManager->persist(new Order('ORD-UNIQ-1', $user));
        $entityManager->flush();

        $entityManager->persist(new Order('ORD-UNIQ-1', $user));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueCheckoutTokens(): void
    {
        $entityManager = $this->integrityEntityManager();
        $user = $this->user();
        $entityManager->persist($user);
        $entityManager->flush();

        $entityManager->persist(new OrderCheckoutSession('checkout-unique-token', $user, 'cart-1', 10, 'stripe-session-1', 'https://stripe.test/1'));
        $entityManager->flush();

        $entityManager->persist(new OrderCheckoutSession('checkout-unique-token', $user, 'cart-2', 11, 'stripe-session-2', 'https://stripe.test/2'));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueCheckoutStripeSessionIds(): void
    {
        $entityManager = $this->integrityEntityManager();
        $user = $this->user();
        $entityManager->persist($user);
        $entityManager->flush();

        $entityManager->persist(new OrderCheckoutSession('checkout-token-1', $user, 'cart-1', 10, 'stripe-session-dup', 'https://stripe.test/1'));
        $entityManager->flush();

        $entityManager->persist(new OrderCheckoutSession('checkout-token-2', $user, 'cart-2', 11, 'stripe-session-dup', 'https://stripe.test/2'));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueProductSkuAndSlug(): void
    {
        $entityManager = $this->integrityEntityManager();
        $category = new Category('Phones', 'phones');
        $entityManager->persist($category);
        $entityManager->persist(new Product('iPhone 1', 'iphone-1', 'SKU-1', 'Desc', 100000, 5, $category));
        $entityManager->flush();

        $entityManager->persist(new Product('iPhone 2', 'iphone-2', 'SKU-1', 'Desc', 110000, 4, $category));

        try {
            $entityManager->flush();
            self::fail('Expected unique SKU violation.');
        } catch (UniqueConstraintViolationException) {
            self::assertTrue(true);
        }

        $entityManager = $this->integrityEntityManager();
        $category = new Category('Phones', 'phones');
        $entityManager->persist($category);
        $entityManager->persist(new Product('iPhone 1', 'iphone-1', 'SKU-1', 'Desc', 100000, 5, $category));
        $entityManager->flush();

        $entityManager->persist(new Product('iPhone 2', 'iphone-1', 'SKU-2', 'Desc', 110000, 4, $category));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaEnforcesUniqueTradeInReferences(): void
    {
        $entityManager = $this->integrityEntityManager();
        $user = $this->user();
        $entityManager->persist($user);
        $entityManager->persist($this->tradeInRequest($user));
        $entityManager->flush();

        $entityManager->persist($this->tradeInRequest($user));

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }

    public function testSqliteSchemaKeepsRepresentativeOrderCheckoutProductAndTradeInConstraintsExplicit(): void
    {
        $entityManager = $this->integrityEntityManager();
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        $productColumns = array_change_key_case($schemaManager->listTableColumns('catalog_products'), \CASE_LOWER);
        self::assertTrue($productColumns['category_id']->getNotnull());
        self::assertTrue($productColumns['slug']->getNotnull());
        self::assertTrue($productColumns['sku']->getNotnull());

        $productFks = $schemaManager->listTableForeignKeys('catalog_products');
        $productFkMap = [];
        foreach ($productFks as $foreignKey) {
            $productFkMap[implode(',', $foreignKey->getLocalColumns())] = $foreignKey->getForeignTableName();
        }
        self::assertSame('catalog_categories', $productFkMap['category_id'] ?? null);
        self::assertSame('catalog_brands', $productFkMap['brand_id'] ?? null);

        $orderColumns = array_change_key_case($schemaManager->listTableColumns('orders'), \CASE_LOWER);
        self::assertTrue($orderColumns['user_id']->getNotnull());
        self::assertTrue($orderColumns['status']->getNotnull());

        $orderFks = $schemaManager->listTableForeignKeys('orders');
        self::assertCount(1, $orderFks);
        self::assertSame(['user_id'], $orderFks[0]->getLocalColumns());
        self::assertSame('users', $orderFks[0]->getForeignTableName());

        $checkoutColumns = array_change_key_case($schemaManager->listTableColumns('order_checkout_sessions'), \CASE_LOWER);
        foreach (['user_id', 'token', 'cart_token', 'customer_email', 'status'] as $column) {
            self::assertTrue($checkoutColumns[$column]->getNotnull(), $column.' should be NOT NULL');
        }

        $checkoutFks = $schemaManager->listTableForeignKeys('order_checkout_sessions');
        self::assertCount(1, $checkoutFks);
        self::assertSame(['user_id'], $checkoutFks[0]->getLocalColumns());
        self::assertSame('users', $checkoutFks[0]->getForeignTableName());

        $tradeInColumns = array_change_key_case($schemaManager->listTableColumns('trade_in_requests'), \CASE_LOWER);
        foreach (['reference', 'status', 'created_at'] as $column) {
            self::assertTrue($tradeInColumns[$column]->getNotnull(), $column.' should be NOT NULL');
        }

        $tradeInFks = $schemaManager->listTableForeignKeys('trade_in_requests');
        self::assertCount(1, $tradeInFks);
        self::assertSame(['user_id'], $tradeInFks[0]->getLocalColumns());
        self::assertSame('users', $tradeInFks[0]->getForeignTableName());
    }

    public function testSqliteSchemaEnforcesTrainingEnrollmentForeignKeys(): void
    {
        $entityManager = $this->integrityEntityManager();
        [, $user] = $this->trainingGraph($entityManager);
        $connection = $entityManager->getConnection();

        try {
            $connection->insert('training_enrollments', [
                'session_id' => 999999,
                'user_id' => (int) $user->getId(),
                'status' => TrainingEnrollment::STATUS_PENDING_PAYMENT,
                'price_cents' => 1500,
                'scheduled_starts_at' => '2026-08-12 09:00:00',
                'scheduled_ends_at' => '2026-08-12 10:00:00',
                'created_at' => '2026-08-11 09:00:00',
            ]);
            self::fail('Expected FK violation for missing training session.');
        } catch (ForeignKeyConstraintViolationException) {
            self::assertTrue(true);
        }
    }

    /**
     * @return array{0: TrainingSession, 1: User}
     */
    private function trainingGraph(\Doctrine\ORM\EntityManager $entityManager): array
    {
        $user = $this->user();
        $training = new Training('SEO', 'seo', 120, 1500);
        $session = new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable('2026-08-12T09:00:00+00:00'),
            new \DateTimeImmutable('2026-08-12T11:00:00+00:00'),
            12,
        );

        $entityManager->persist($user);
        $entityManager->persist($training);
        $entityManager->persist($session);
        $entityManager->flush();

        self::assertInstanceOf(User::class, $user);

        return [$session, $user];
    }
}
