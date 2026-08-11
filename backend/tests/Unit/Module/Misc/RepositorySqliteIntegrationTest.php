<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartItemRepository;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Order\Infrastructure\Repository\StripeWebhookEventRepository;

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
}
