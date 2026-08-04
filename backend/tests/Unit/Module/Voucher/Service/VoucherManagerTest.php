<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Service;

use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Handler\DeleteVoucherHandler;
use App\Module\Voucher\Application\Handler\UpdateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
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
    private EntityManager $entityManager;

    public function testCreateNormalizesOptionalFieldsAndDeleteRemovesVoucher(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Voucher::class));
        $entityManager->expects(self::exactly(2))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(Voucher::class));
        $persistence = new DoctrineUnitOfWork($entityManager);
        $createVoucher = $this->createVoucher($repository, $persistence);
        $deleteVoucher = new DeleteVoucherHandler($persistence);

        $voucher = $createVoucher->create([
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

        $deleteVoucher->delete($voucher);
    }

    public function testUpdatePreservesOptionalFieldsWhenTheyAreAbsent(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $manager = $this->updateVoucher($repository, new DoctrineUnitOfWork($entityManager));

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
        $manager = $this->updateVoucher($repository, new DoctrineUnitOfWork($entityManager));

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

    public function testCreateVoucherRejectsInvalidDateRangeAndPercentOverflow(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $manager = $this->createVoucher($repository, new DoctrineUnitOfWork($entityManager));

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

    public function testCreateVoucherRejectsRequiredInvalidTypeInvalidValueAndExistingCode(): void
    {
        $repository = $this->repository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $manager = $this->createVoucher($repository, new DoctrineUnitOfWork($entityManager));

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

        $repository->save(new Voucher('Existing', 'DUPLICATE', Voucher::TYPE_FIXED_CENTS, 500));
        $this->entityManager->flush();

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
        $repository->save($voucher);
        $this->entityManager->flush();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $manager = $this->updateVoucher($repository, new DoctrineUnitOfWork($entityManager));

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

    public function testCreateVoucherConvertsSqlUniqueViolationToReadableMessage(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())
            ->method('flush')
            ->willThrowException($this->uniqueConstraint('Duplicate entry for vouchers.code'));
        $manager = $this->createVoucher($this->repository(), new DoctrineUnitOfWork($entityManager));

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
        $this->entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([$this->entityManager->getClassMetadata(Voucher::class)]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new VoucherRepository($registry);
    }

    private function createVoucher(VoucherRepository $repository, DoctrineUnitOfWork $persistence): CreateVoucherHandler
    {
        return new CreateVoucherHandler($persistence, new VoucherPayload($repository));
    }

    private function updateVoucher(VoucherRepository $repository, DoctrineUnitOfWork $persistence): UpdateVoucherHandler
    {
        return new UpdateVoucherHandler($persistence, new VoucherPayload($repository));
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
