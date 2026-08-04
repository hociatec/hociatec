<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Repository;

use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class VoucherRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([
            $this->entityManager->getClassMetadata(Voucher::class),
        ]);
    }

    public function testRepositoryMethodsOperateOnRealQueries(): void
    {
        $active = (new Voucher('Active', 'ACTIVE', Voucher::TYPE_FIXED_CENTS, 1000))
            ->setRecipientUserId(42)
            ->setStartsAt(new \DateTimeImmutable('2026-07-01T00:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-08-01T00:00:00+00:00'));
        $expired = (new Voucher('Expired', 'EXPIRED', Voucher::TYPE_FIXED_CENTS, 1000))
            ->setRecipientUserId(42)
            ->setStartsAt(new \DateTimeImmutable('2026-06-01T00:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-06-30T00:00:00+00:00'));

        $repository = $this->repository();
        $repository->save($active);
        $repository->save($expired, true);

        self::assertCount(1, $repository->findActiveForDate(new \DateTimeImmutable('2026-07-29T00:00:00+00:00')));
        self::assertSame('ACTIVE', $repository->findOneByCode(' active ') ?->getCode());
        self::assertNull($repository->findOneByCode('   '));
        self::assertCount(2, $repository->findByRecipientUserId(42));
    }

    private function repository(): VoucherRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new VoucherRepository($registry);
    }
}
