<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Persistence;

use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DoctrinePersistenceTest extends TestCase
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

        $persistence = new DoctrinePersistence($entityManager);

        $persistence->persist($entity);
        $persistence->remove($entity);
        $persistence->flush();
        self::assertSame($queryBuilder, $persistence->queryBuilder());
        $persistence->clear();
    }

    public function testItFindsEntitiesForUpdateAndWrapsTransactions(): void
    {
        $entity = new \stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(\stdClass::class, 12, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($entity);
        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $operation): mixed => $operation());

        $persistence = new DoctrinePersistence($entityManager);

        self::assertSame($entity, $persistence->findForUpdate(\stdClass::class, 12));
        self::assertSame('done', $persistence->transactional(static fn (): string => 'done'));
    }
}
