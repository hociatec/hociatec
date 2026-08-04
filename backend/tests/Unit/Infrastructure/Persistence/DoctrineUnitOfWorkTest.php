<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DoctrineUnitOfWorkTest extends TestCase
{
    public function testItDelegatesBasicUnitOfWorkOperations(): void
    {
        $entity = new \stdClass();
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($entity);
        $entityManager->expects(self::once())->method('remove')->with($entity);
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::once())->method('clear');
        $entityManager->expects(self::once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $persistence = new DoctrineUnitOfWork($entityManager);

        $persistence->persist($entity);
        $persistence->remove($entity);
        $persistence->commit();
        self::assertSame($queryBuilder, $persistence->queryBuilder());
        $persistence->clear();
    }

    public function testItFindsEntitiesForUpdate(): void
    {
        $entity = new \stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(\stdClass::class, 12, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($entity);

        $persistence = new DoctrineUnitOfWork($entityManager);

        self::assertSame($entity, $persistence->findForUpdate(\stdClass::class, 12));
    }

    public function testTransactionManagerWrapsTransactions(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $operation): mixed => $operation());

        self::assertSame('done', (new DoctrineTransactionManager($entityManager))->transactional(static fn (): string => 'done'));
    }
}
