<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Service;

use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherManager;
use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class VoucherManagerTest extends TestCase
{
    public function testUpdatePreservesOptionalFieldsWhenTheyAreAbsent(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $manager = new VoucherManager($repository, new DoctrinePersistence($entityManager));

        $voucher = (new Voucher('Voucher', 'CODE', Voucher::TYPE_FIXED_CENTS, 500))
            ->setDescription('Existing')
            ->setIsActive(false)
            ->setStartsAt(new \DateTimeImmutable('2026-07-29T10:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-07-30T10:00:00+00:00'));

        $updated = $manager->update($voucher, [
            'name' => 'Voucher 2',
            'code' => ' code-2 ',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 700,
        ]);

        self::assertSame($voucher, $updated);
        self::assertSame('Existing', $updated->getDescription());
        self::assertFalse($updated->isActive());
        self::assertSame('2026-07-29T10:00:00+00:00', $updated->getStartsAt()?->format(DATE_ATOM));
        self::assertSame('2026-07-30T10:00:00+00:00', $updated->getEndsAt()?->format(DATE_ATOM));
    }

    public function testUpdateAllowsExplicitNullForOptionalFields(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $manager = new VoucherManager($repository, new DoctrinePersistence($entityManager));

        $voucher = (new Voucher('Voucher', 'CODE', Voucher::TYPE_FIXED_CENTS, 500))
            ->setDescription('Existing')
            ->setStartsAt(new \DateTimeImmutable('2026-07-29T10:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-07-30T10:00:00+00:00'));

        $updated = $manager->update($voucher, [
            'name' => 'Voucher 2',
            'code' => 'CODE-2',
            'description' => null,
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 700,
            'startsAt' => null,
            'endsAt' => null,
        ]);

        self::assertNull($updated->getDescription());
        self::assertNull($updated->getStartsAt());
        self::assertNull($updated->getEndsAt());
    }

    public function testManagerRejectsInvalidDateRangeAndPercentOverflow(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $manager = new VoucherManager($repository, new DoctrinePersistence($entityManager));

        try {
            $manager->create([
                'name' => 'Voucher',
                'code' => 'CODE',
                'discountType' => Voucher::TYPE_PERCENT,
                'discountValue' => 101,
            ]);
            self::fail('Expected invalid percent exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La remise en pourcentage ne peut pas dépasser 100 %.', $exception->getMessage());
        }

        try {
            $manager->create([
                'name' => 'Voucher',
                'code' => 'CODE',
                'discountType' => Voucher::TYPE_FIXED_CENTS,
                'discountValue' => 500,
                'startsAt' => new \DateTimeImmutable('2026-07-30T10:00:00+00:00'),
                'endsAt' => new \DateTimeImmutable('2026-07-30T10:00:00+00:00'),
            ]);
            self::fail('Expected invalid date range exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La date de fin doit être postérieure à la date de début.', $exception->getMessage());
        }
    }

    public function testManagerConvertsSqlUniqueViolationToReadableMessage(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())
            ->method('flush')
            ->willThrowException($this->uniqueConstraint('Duplicate entry for vouchers.code'));
        $manager = new VoucherManager($this->repository(), new DoctrinePersistence($entityManager));

        try {
            $manager->create([
                'name' => 'Voucher',
                'code' => 'CODE',
                'discountType' => Voucher::TYPE_FIXED_CENTS,
                'discountValue' => 500,
            ]);
            self::fail('Expected duplicate code exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Ce code existe déjà.', $exception->getMessage());
            self::assertInstanceOf(UniqueConstraintViolationException::class, $exception->getPrevious());
        }
    }

    private function repository(): VoucherRepository
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata(Voucher::class)]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new VoucherRepository($registry);
    }

    private function uniqueConstraint(string $message): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(
            new class($message) extends \RuntimeException implements DriverException {
                public function getSQLState(): ?string
                {
                    return null;
                }
            },
            null
        );
    }
}
