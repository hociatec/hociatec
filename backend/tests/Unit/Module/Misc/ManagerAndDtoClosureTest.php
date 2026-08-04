<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Application\Service\PromotionManager;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Service\UserPersistence;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Application\Service\VoucherManager;
use App\Infrastructure\Persistence\DoctrinePersistence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ManagerAndDtoClosureTest extends TestCase
{
    public function testNotificationReadStateInputFromArray(): void
    {
        $input = NotificationReadStateInput::fromArray([
            'seenKeys' => [' a ', '', 1],
            'dismissedKey' => ' x ',
            'dismissedKeys' => [' y ', '', 2],
            'seenSignature' => " sig \n",
        ]);

        self::assertSame(['a'], $input->seenKeys);
        self::assertSame('x', $input->dismissedKey);
        self::assertSame(['y'], $input->dismissedKeys);
        self::assertSame('sig', $input->seenSignature);
    }

    public function testUserPersistenceDelegatesTransactionAndSave(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(User::class));
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::once())->method('wrapInTransaction')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $persistence = new UserPersistence($entityManager);
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        $persistence->save($user);
        $persistence->flush();
        self::assertSame('done', $persistence->transactional(static fn (): string => 'done'));
    }

    public function testPromotionManagerCreateUpdateAndDelete(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Promotion::class));
        $entityManager->expects(self::exactly(3))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(Promotion::class));
        $manager = new PromotionManager(new DoctrinePersistence($entityManager));

        $input = PromotionInput::fromArray([
            'name' => ' Promo ',
            'slug' => 'promo',
            'discountType' => Promotion::TYPE_PERCENT,
            'discountValue' => 15,
            'audienceKey' => 'all_users',
            'criteria' => ['country' => 'FR'],
            'description' => ' Desc ',
            'isActive' => true,
            'startsAt' => '2026-07-01T10:00:00+00:00',
            'endsAt' => '2026-08-01T10:00:00+00:00',
        ]);
        $promotion = $manager->create($input);

        self::assertSame('Promo', $promotion->getName());
        self::assertSame('promo', $promotion->getSlug());
        self::assertSame(Promotion::TYPE_PERCENT, $promotion->getDiscountType());
        self::assertSame(15, $promotion->getDiscountValue());
        self::assertSame('all_users', $promotion->getAudienceKey());
        self::assertSame(['country' => 'FR'], $promotion->getCriteria());
        self::assertSame('Desc', $promotion->getDescription());

        $updated = $manager->update($promotion, new PromotionInput(
            'Promo 2',
            'promo-2',
            Promotion::TYPE_FIXED_CENTS,
            2500,
            'vip',
            ['minimumOrders' => 2],
            'Desc 2',
            false,
            null,
            null,
        ));
        self::assertSame('Promo 2', $updated->getName());
        self::assertSame('promo-2', $updated->getSlug());
        self::assertSame(Promotion::TYPE_FIXED_CENTS, $updated->getDiscountType());
        self::assertSame(2500, $updated->getDiscountValue());
        self::assertSame('vip', $updated->getAudienceKey());
        self::assertSame(['minimumOrders' => 2], $updated->getCriteria());
        self::assertSame('Desc 2', $updated->getDescription());
        self::assertFalse($updated->isActive());

        $manager->delete($updated);
    }

    public function testVoucherManagerValidatesCreateUpdateAndDelete(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::atLeast(1))->method('persist');
        $entityManager->expects(self::atLeast(1))->method('flush');
        $persistence = new DoctrinePersistence($entityManager);
        $repo = $this->voucherRepository();
        $manager = new VoucherManager($repo, $persistence);

        $voucher = $manager->create([
            'name' => 'Voucher',
            'code' => ' test ',
            'description' => ' desc ',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 500,
            'isActive' => true,
        ]);

        self::assertSame('Voucher', $voucher->getName());
        self::assertSame('TEST', $voucher->getCode());
        self::assertSame('desc', $voucher->getDescription());

        $updated = $manager->update($voucher, [
            'name' => 'Voucher 2',
            'code' => ' test-2 ',
            'description' => ' text ',
            'discountType' => Voucher::TYPE_PERCENT,
            'discountValue' => 10,
            'isActive' => false,
        ]);
        self::assertSame('Voucher 2', $updated->getName());
        self::assertSame('TEST-2', $updated->getCode());
        self::assertSame('text', $updated->getDescription());
        self::assertSame(Voucher::TYPE_PERCENT, $updated->getDiscountType());
        self::assertSame(10, $updated->getDiscountValue());
        self::assertFalse($updated->isActive());

        try {
            $manager->create([
                'name' => '',
                'code' => '',
                'discountType' => '',
                'discountValue' => 0,
            ]);
            self::fail('Expected validation exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Champs obligatoires manquants.', $exception->getMessage());
        }

        try {
            $manager->create([
                'name' => 'Voucher',
                'code' => 'A',
                'discountType' => 'weird',
                'discountValue' => 10,
            ]);
            self::fail('Expected type exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Type de remise invalide.', $exception->getMessage());
        }

        try {
            $manager->update($updated, [
                'name' => 'Voucher 2',
                'code' => ' test-2 ',
                'discountType' => Voucher::TYPE_PERCENT,
                'discountValue' => 0,
            ]);
            self::fail('Expected discount value exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La valeur de remise doit être supérieure à zéro.', $exception->getMessage());
        }

        $manager->delete($updated);
    }

    public function testVoucherManagerRejectsDuplicateCodesFromRepository(): void
    {
        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new \Doctrine\ORM\EntityManager($connection, $config);
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata(Voucher::class)]);

        $registry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        $repo = new \App\Module\Voucher\Infrastructure\Repository\VoucherRepository($registry);
        $manager = new VoucherManager($repo, new DoctrinePersistence($entityManager));

        $manager->create([
            'name' => 'Existing',
            'code' => 'duplicate',
            'discountType' => Voucher::TYPE_PERCENT,
            'discountValue' => 10,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ce code existe déjà.');
        $manager->create([
            'name' => 'Voucher',
            'code' => 'duplicate',
            'discountType' => Voucher::TYPE_PERCENT,
            'discountValue' => 10,
        ]);
    }

    private function voucherRepository(): \App\Module\Voucher\Infrastructure\Repository\VoucherRepository
    {
        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new \Doctrine\ORM\EntityManager($connection, $config);
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata(Voucher::class)]);

        $registry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new \App\Module\Voucher\Infrastructure\Repository\VoucherRepository($registry);
    }
}
