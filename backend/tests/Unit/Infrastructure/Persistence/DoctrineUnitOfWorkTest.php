<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence;

use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class DoctrineUnitOfWorkTest extends TestCase
{
    public function testItDelegatesBasicUnitOfWorkOperations(): void
    {
        $entity = new \stdClass();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($entity);
        $entityManager->expects(self::once())->method('remove')->with($entity);
        $entityManager->expects(self::once())->method('flush');

        $persistence = new DoctrineUnitOfWork($entityManager);

        $persistence->persist($entity);
        $persistence->remove($entity);
        $persistence->commit();
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
