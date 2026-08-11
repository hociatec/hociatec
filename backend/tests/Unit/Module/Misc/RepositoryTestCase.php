<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\User\Domain\Entity\User;
use App\Tests\Support\TradeInRequestFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class RepositoryTestCase extends TestCase
{
    protected function repository(string $repositoryClass, string $entityClass): object
    {
        return new $repositoryClass($this->registry($entityClass));
    }

    protected function registry(string $entityClass): ManagerRegistry&MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with($entityClass)->willReturn(new ClassMetadata($entityClass));

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with($entityClass)->willReturn($entityManager);

        return $registry;
    }

    protected function queryBuilderReturning(array $result): QueryBuilder&MockObject
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

    protected function entityManagerForRemoval(object $entity): EntityManagerInterface&MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($entity);

        return $entityManager;
    }

    protected function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    protected function tradeInRequest(User $user): TradeInRequest
    {
        return TradeInRequestFactory::submitted(
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

    protected function repositoryEntityManager(): EntityManager
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
    protected function repositoryWithEntityManager(string $repositoryClass, EntityManager $entityManager): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new $repositoryClass($registry);
    }
}
