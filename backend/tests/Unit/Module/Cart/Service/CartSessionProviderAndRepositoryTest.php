<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Cart\Service;

use App\Module\Cart\Application\Provider\CartSessionProvider;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class CartSessionProviderAndRepositoryTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testFindByTokenAndRepositoryMethodsNormalizeMissingTokens(): void
    {
        $user = $this->persistUser();
        $cart = new CartSession('token-1');
        $cart->setUser($user);
        $this->entityManager()->persist($cart);
        $this->entityManager()->flush();

        $repository = $this->repository();
        $provider = new CartSessionProvider($repository, new DoctrineUnitOfWork($this->entityManager()));

        self::assertNull($provider->findByToken(null));
        self::assertNull($provider->findByToken('   '));
        self::assertSame($cart, $provider->findByToken(' token-1 '));
        self::assertSame($cart, $repository->findOneByToken('token-1'));
        self::assertSame($cart, $repository->findOneByUser($user));
        self::assertSame($cart, $repository->findOneByUserId((int) $user->getId()));
    }

    public function testViewReturnsExistingCartCreatesMissingAndReplacesConvertedCarts(): void
    {
        $user = $this->persistUser('converted@example.test');
        $active = new CartSession('active-token');
        $convertedGuest = (new CartSession('guest-converted'))->markConverted(1001);
        $convertedUser = (new CartSession('user-converted'))->setUser($user)->markConverted(1002);

        foreach ([$active, $convertedGuest, $convertedUser] as $cart) {
            $this->entityManager()->persist($cart);
        }
        $this->entityManager()->flush();

        $provider = new CartSessionProvider($this->repository(), new DoctrineUnitOfWork($this->entityManager()));

        self::assertSame($active, $provider->view('active-token'));

        $created = $provider->view('missing-token');
        self::assertNotSame('missing-token', $created->getToken());
        self::assertFalse($created->isConverted());

        $replacementGuest = $provider->view('guest-converted');
        self::assertNotSame($convertedGuest, $replacementGuest);
        self::assertNull($replacementGuest->getUser());

        $replacementUser = $provider->view('user-converted');
        self::assertNotSame($convertedUser, $replacementUser);
        self::assertSame($user, $replacementUser->getUser());
    }

    public function testResolveForMutationPrefersUserCartThenReusableTokenAndCreatesFallbacks(): void
    {
        $user = $this->persistUser('user-cart@example.test');
        $userCart = (new CartSession('user-cart'))->setUser($user);
        $guestCart = new CartSession('guest-cart');
        $convertedGuest = (new CartSession('converted-token'))->markConverted(1003);

        foreach ([$userCart, $guestCart, $convertedGuest] as $cart) {
            $this->entityManager()->persist($cart);
        }
        $this->entityManager()->flush();

        $provider = new CartSessionProvider($this->repository(), new DoctrineUnitOfWork($this->entityManager()));

        self::assertSame($userCart, $provider->resolveForMutation('guest-cart', $user));
        self::assertSame($guestCart, $provider->resolveForMutation('guest-cart', null));

        $createdForUser = $provider->resolveForMutation('converted-token', $this->persistUser('new@example.test'));
        self::assertNotSame($convertedGuest, $createdForUser);
        self::assertSame('new@example.test', $createdForUser->getUser()?->getEmail());

        $createdGuest = $provider->resolveForMutation('converted-token', null);
        self::assertNotSame($convertedGuest, $createdGuest);
        self::assertNull($createdGuest->getUser());
    }

    private function persistUser(string $email = 'ada@example.test'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
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
