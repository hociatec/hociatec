<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Service;

use App\Module\Cart\Application\Provider\CartSessionProvider;
use App\Module\Cart\Application\Workflow\CartVoucherService;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Voucher\Application\Calculator\VoucherEngine;
use App\Module\Voucher\Application\Port\VoucherLookupPort;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class CartVoucherServiceTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testApplyPersistsVoucherCodeWhenVoucherEngineAcceptsItAndClearRemovesIt(): void
    {
        $cart = new CartSession('cart-token');
        $product = $this->product();
        $cart->addItem(new CartItem($cart, $product, 1));
        $this->entityManager()->persist($product->getCategory());
        $this->entityManager()->persist($product);
        foreach ($cart->getItems() as $item) {
            $this->entityManager()->persist($item);
        }
        $this->entityManager()->persist($cart);
        $this->entityManager()->flush();

        $service = $this->service(new Voucher('Summer', 'SUMMER10', Voucher::TYPE_FIXED_CENTS, 1000));

        $updated = $service->apply('cart-token', ' SUMMER10 ');
        self::assertSame('SUMMER10', $updated->getVoucherCode());

        $cleared = $service->clear('cart-token');
        self::assertNull($cleared->getVoucherCode());
    }

    public function testApplyRejectsInvalidAndIneligibleVoucherStatuses(): void
    {
        $cases = [
            [null, 'NOPE', 'Bon de réduction invalide.'],
            [new Voucher('Zero', 'ZERO', Voucher::TYPE_FIXED_CENTS, 1000), 'ZERO', 'Ce bon de réduction n\'est pas éligible pour ce panier.'],
        ];

        foreach ($cases as [$voucher, $code, $message]) {
            $service = $this->service($voucher);
            try {
                $service->apply(null, $code);
                self::fail('Expected voucher exception.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame($message, $exception->getMessage());
            }
        }
    }

    private function service(?Voucher $voucher): CartVoucherService
    {
        $entityManager = $this->entityManager();
        $persistence = new DoctrineUnitOfWork($entityManager);
        $provider = new CartSessionProvider($this->repository(), $persistence);

        return new CartVoucherService(
            $provider,
            new VoucherEngine($this->voucherLookup($voucher), new \App\Module\Voucher\Application\Projection\VoucherFormatter()),
            $persistence,
        );
    }

    private function voucherLookup(?Voucher $voucher): VoucherLookupPort
    {
        return new class($voucher) implements VoucherLookupPort {
            public function __construct(private readonly ?Voucher $voucher)
            {
            }

            public function findOneByCode(?string $code): ?Voucher
            {
                return $this->voucher instanceof Voucher && $this->voucher->getCode() === $code
                    ? $this->voucher
                    : null;
            }
        };
    }

    private function product(): Product
    {
        return new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, new Category('Phones', 'phones'));
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(CartSession::class),
            $entityManager->getClassMetadata(CartItem::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function repository(): CartSessionRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new CartSessionRepository($registry);
    }
}
