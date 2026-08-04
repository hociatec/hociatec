<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Repository;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class OrderRepositoryTest extends TestCase
{
    public function testFindForUpdateReturnsOrderOrNull(): void
    {
        $order = new Order('ORD-1', $this->user());
        $repository = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $repository->expects(self::exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($order, new \stdClass());

        self::assertSame($order, $repository->findForUpdate(1));
        self::assertNull($repository->findForUpdate(2));
    }

    public function testCountAndListQueriesReturnExpectedValues(): void
    {
        $user = $this->user();
        $order = new Order('ORD-2026-0001', $user);

        $repository = $this->repositoryWithBuilders([
            $this->scalarBuilder('4'),
            $this->scalarBuilder('2'),
            $this->resultBuilder([$order]),
            $this->resultBuilder([$order]),
            $this->resultBuilder([$order]),
            $this->resultBuilder([$order]),
        ]);

        self::assertSame(4, $repository->countForYear(2026));
        self::assertSame(2, $repository->countInvoicedForYear(2026));
        self::assertSame([$order], $repository->findByUser($user));
        self::assertSame([$order], $repository->findRecentForAdmin(0));
        self::assertSame([$order], $repository->findPendingPaymentForAdmin(3));
        self::assertSame([$order], $repository->findFulfillmentQueue(5));
    }

    public function testSummaryStatusCountsAndOperationalIssuesAreComputed(): void
    {
        $repository = $this->repositoryWithBuilders([
            $this->singleResultBuilder(['ordersCount' => '3', 'totalCents' => '9999']),
            $this->arrayResultBuilder([
                ['status' => Order::STATUS_PENDING, 'count' => '2'],
                ['status' => '', 'count' => '9'],
                ['status' => Order::STATUS_DELIVERED, 'count' => '1'],
            ]),
            $this->scalarBuilder('7', withExpr: true),
        ]);

        self::assertSame(
            ['count' => 3, 'totalCents' => 9999],
            $repository->getSummaryBetween(new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-07-31')),
        );

        self::assertSame([
            Order::STATUS_PENDING => 2,
            Order::STATUS_CONFIRMED => 0,
            Order::STATUS_DELIVERED => 1,
            Order::STATUS_CANCELLED => 0,
        ], $repository->getStatusCounts());

        self::assertSame(7, $repository->countWithOperationalIssues());
    }

    /**
     * @param list<QueryBuilder> $builders
     */
    private function repositoryWithBuilders(array $builders): OrderRepository
    {
        $repository = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->method('createQueryBuilder')->willReturnOnConsecutiveCalls(...$builders);

        return $repository;
    }

    private function scalarBuilder(string $result, bool $withExpr = false): QueryBuilder
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSingleScalarResult'])
            ->getMock();
        $query->method('getSingleScalarResult')->willReturn($result);

        return $this->builder($query, $withExpr);
    }

    private function resultBuilder(array $result): QueryBuilder
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMock();
        $query->method('getResult')->willReturn($result);

        return $this->builder($query);
    }

    private function singleResultBuilder(array $result): QueryBuilder
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSingleResult'])
            ->getMock();
        $query->method('getSingleResult')->willReturn($result);

        return $this->builder($query);
    }

    private function arrayResultBuilder(array $result): QueryBuilder
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArrayResult'])
            ->getMock();
        $query->method('getArrayResult')->willReturn($result);

        return $this->builder($query);
    }

    private function builder(Query $query, bool $withExpr = false): QueryBuilder
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'select',
                'andWhere',
                'setParameter',
                'orderBy',
                'setMaxResults',
                'getQuery',
                'groupBy',
                'leftJoin',
                'expr',
            ])
            ->getMock();

        foreach (['select', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'groupBy', 'leftJoin'] as $method) {
            $qb->method($method)->willReturn($qb);
        }
        $qb->method('getQuery')->willReturn($query);

        if ($withExpr) {
            $qb->method('expr')->willReturn(new Expr());
        }

        return $qb;
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }
}
