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
    public function testCreateNormalizesOptionalFieldsAndDeleteRemovesVoucher(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Voucher::class));
        $entityManager->expects(self::exactly(2))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(Voucher::class));
        $manager = new VoucherManager($repository, new DoctrinePersistence($entityManager));

        $voucher = $manager->create([
            'name' => ' Voucher ',
            'code' => ' code ',
            'description' => '   ',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 500,
        ]);

        self::assertSame('Voucher', $voucher->getName());
        self::assertSame('CODE', $voucher->getCode());
        self::assertNull($voucher->getDescription());
        self::assertTrue($voucher->isActive());

        $manager->delete($voucher);
    }

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

    public function testManagerRejectsRequiredInvalidTypeInvalidValueAndExistingCode(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $manager = new VoucherManager($repository, new DoctrinePersistence($entityManager));

        foreach ([
            [
                'data' => [
                    'name' => ' ',
                    'code' => 'CODE',
                    'discountType' => Voucher::TYPE_FIXED_CENTS,
                    'discountValue' => 500,
                ],
                'message' => 'Champs obligatoires manquants.',
            ],
            [
                'data' => [
                    'name' => 'Voucher',
                    'code' => 'CODE',
                    'discountType' => 'bogus',
                    'discountValue' => 500,
                ],
                'message' => 'Type de remise invalide.',
            ],
            [
                'data' => [
                    'name' => 'Voucher',
                    'code' => 'CODE',
                    'discountType' => Voucher::TYPE_FIXED_CENTS,
                    'discountValue' => 0,
                ],
                'message' => 'La valeur de remise doit être supérieure à zéro.',
            ],
        ] as $case) {
            try {
                $manager->create($case['data']);
                self::fail('Expected validation exception.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame($case['message'], $exception->getMessage());
            }
        }

        $repository->save(new Voucher('Existing', 'DUPLICATE', Voucher::TYPE_FIXED_CENTS, 500), true);

        try {
            $manager->create([
                'name' => 'Voucher',
                'code' => 'duplicate',
                'description' => 'Existing code',
                'discountType' => Voucher::TYPE_FIXED_CENTS,
                'discountValue' => 500,
            ]);
            self::fail('Expected duplicate code exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Ce code existe déjà.', $exception->getMessage());
        }
    }

    public function testUpdateAcceptsCurrentVoucherCodeWhenRepositoryFindsSameEntity(): void
    {
        $repository = $this->repository();
        $voucher = new Voucher('Existing', 'SAME', Voucher::TYPE_FIXED_CENTS, 500);
        $repository->save($voucher, true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $manager = new VoucherManager($repository, new DoctrinePersistence($entityManager));

        $updated = $manager->update($voucher, [
            'name' => 'Updated',
            'code' => 'same',
            'description' => ' Updated description ',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 600,
            'isActive' => false,
        ]);

        self::assertSame($voucher, $updated);
        self::assertSame('SAME', $updated->getCode());
        self::assertSame('Updated description', $updated->getDescription());
        self::assertFalse($updated->isActive());
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
