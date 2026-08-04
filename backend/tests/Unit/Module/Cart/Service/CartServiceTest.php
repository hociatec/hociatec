<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Service;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Cart\Application\Provider\CartItemResolver;
use App\Module\Cart\Application\Workflow\CartService;
use App\Module\Cart\Application\Provider\CartSessionProvider;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class CartServiceTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testAddUpdateRemoveAndClearCartCoverMainFlows(): void
    {
        $entityManager = $this->entityManager();
        $product = $this->persistProduct('Phone', 'PH-1', 10);
        $freshProduct = $this->persistProduct('Tablet', 'TB-1', 10);
        $rentalProduct = $this->persistProduct('MacBook', 'MB-1', 10, 'rental');

        $service = $this->service();
        $cart = $service->addProduct(null, $product, 2);

        self::assertSame(2, $cart->getItemForProduct($product)?->getQuantity());

        $service->addProduct($cart->getToken(), $product, 3);
        $reloadedCart = $this->reloadCart($cart);
        self::assertSame(5, $reloadedCart->getItemForProduct($product)?->getQuantity());

        $service->addProduct($cart->getToken(), $freshProduct, 1);
        self::assertCount(2, $this->reloadCart($cart)->getItems());

        $service->updateProductQuantity($cart->getToken(), $freshProduct, 4);
        $reloadedCart = $this->reloadCart($cart);
        self::assertSame(4, $reloadedCart->getItemForProduct($freshProduct)?->getQuantity());

        $service->updateProductQuantity($cart->getToken(), $freshProduct, 0);
        $reloadedCart = $this->reloadCart($cart);
        self::assertNull($reloadedCart->getItemForProduct($freshProduct));

        $service->addProduct($cart->getToken(), $rentalProduct, 1, 6);
        $service->addProduct($cart->getToken(), $rentalProduct, 2, 12);
        $reloadedCart = $this->reloadCart($cart);
        self::assertCount(2, $reloadedCart->getItemsForProduct($rentalProduct));

        $service->removeProduct($cart->getToken(), $product);
        $reloadedCart = $this->reloadCart($cart);
        self::assertNull($reloadedCart->getItemForProduct($product));

        $service->clearCart($cart->getToken());
        self::assertCount(0, $this->reloadCart($cart)->getItems());
    }

    public function testUpdateProductQuantityMergesRentalLinesWhenTargetMonthsAlreadyExist(): void
    {
        $product = $this->persistProduct('MacBook', 'MB-1', 20, 'rental');
        $service = $this->service();
        $cart = $service->addProduct(null, $product, 1, 6);
        $service->addProduct($cart->getToken(), $product, 3, 12);

        $service->updateProductQuantity($cart->getToken(), $product, 2, 12, 6);

        $reloaded = $this->reloadCart($cart);
        $lines = $reloaded->getItemsForProduct($product);
        self::assertCount(1, $lines);
        self::assertSame(5, $lines[0]->getQuantity());
        self::assertSame(12, $lines[0]->getRentalMonths());
    }

    public function testServiceRejectsInvalidInputsAndStockOverflows(): void
    {
        $product = $this->persistProduct('Phone', 'PH-1', 2);
        $service = $this->service();

        try {
            $service->addProduct(null, $product, 0);
            self::fail('Expected invalid quantity exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La quantite doit etre superieure ou egale a 1.', $exception->getMessage());
        }

        $cart = $service->addProduct(null, $product, 1);

        try {
            $service->updateProductQuantity($cart->getToken(), $product, -1);
            self::fail('Expected invalid update quantity exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La quantite doit etre superieure ou egale a 0.', $exception->getMessage());
        }

        try {
            $service->removeProduct('missing-token', $product);
            self::fail('Expected missing cart exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Panier introuvable.', $exception->getMessage());
        }

        try {
            $service->addProduct($cart->getToken(), $product, 2);
            self::fail('Expected stock exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Stock insuffisant pour ce produit.', $exception->getMessage());
        }
    }

    public function testRentalProductRequiresRentalMonthsWhenMissing(): void
    {
        $product = $this->persistProduct('MacBook', 'MB-1', 10, 'rental');
        $service = $this->service();

        try {
            $service->addProduct(null, $product, 1);
            self::fail('Expected rental months exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Champ "rentalMonths" requis pour ce produit.', $exception->getMessage());
        }
    }

    private function service(): CartService
    {
        $entityManager = $this->entityManager();
        $persistence = new DoctrineUnitOfWork($entityManager);
        $provider = new CartSessionProvider($this->cartRepository($entityManager), $persistence);

        return new CartService($provider, new CartItemResolver(), $persistence, new ProductRepository($this->registry($entityManager)));
    }

    private function reloadCart(CartSession $cart): CartSession
    {
        $this->entityManager()->refresh($cart);

        return $cart;
    }

    private function persistProduct(string $name, string $sku, int $stock, string $sellingType = 'sale'): Product
    {
        $category = new Category('Tech '.$sku, 'tech-'.strtolower($sku));
        $product = new Product($name, strtolower($name).'-'.strtolower($sku), $sku, 'Desc', 10000, $stock, $category);
        $product->setSellingType($sellingType);

        $entityManager = $this->entityManager();
        $entityManager->persist($category);
        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(CartSession::class),
            $entityManager->getClassMetadata(CartItem::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function cartRepository(EntityManager $entityManager): CartSessionRepository
    {
        return new CartSessionRepository($this->registry($entityManager));
    }

    private function registry(EntityManager $entityManager): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return $registry;
    }
}
