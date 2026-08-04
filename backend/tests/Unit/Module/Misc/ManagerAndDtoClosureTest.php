<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Application\Service\CreatePromotionHandler;
use App\Module\Promotion\Application\Service\DeletePromotionHandler;
use App\Module\Promotion\Application\Service\PromotionDataApplier;
use App\Module\Promotion\Application\Service\UpdatePromotionHandler;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Service\UserPersistence;
use App\Module\Voucher\Application\Service\CreateVoucherHandler;
use App\Module\Voucher\Application\Service\DeleteVoucherHandler;
use App\Module\Voucher\Application\Service\UpdateVoucherHandler;
use App\Module\Voucher\Application\Service\VoucherPayload;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
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

    public function testUserPersistenceDelegatesSaveAndFlush(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(User::class));
        $entityManager->expects(self::once())->method('flush');
        $persistence = new UserPersistence($entityManager);
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        $persistence->save($user);
        $persistence->commit();
    }

    public function testDoctrineTransactionManagerDelegatesTransaction(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('wrapInTransaction')->willReturnCallback(static fn (callable $operation): mixed => $operation());

        self::assertSame('done', (new DoctrineTransactionManager($entityManager))->transactional(static fn (): string => 'done'));
    }

    public function testPromotionHandlersCreateUpdateAndDelete(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Promotion::class));
        $entityManager->expects(self::exactly(3))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(Promotion::class));
        $persistence = new DoctrineUnitOfWork($entityManager);
        $applier = new PromotionDataApplier();
        $createPromotion = new CreatePromotionHandler($persistence, $applier);
        $updatePromotion = new UpdatePromotionHandler($persistence, $applier);
        $deletePromotion = new DeletePromotionHandler($persistence);

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
        $promotion = $createPromotion->create($input);

        self::assertSame('Promo', $promotion->getName());
        self::assertSame('promo', $promotion->getSlug());
        self::assertSame(Promotion::TYPE_PERCENT, $promotion->getDiscountType());
        self::assertSame(15, $promotion->getDiscountValue());
        self::assertSame('all_users', $promotion->getAudienceKey());
        self::assertSame(['country' => 'FR'], $promotion->getCriteria());
        self::assertSame('Desc', $promotion->getDescription());

        $updated = $updatePromotion->update($promotion, new PromotionInput(
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

        $deletePromotion->delete($updated);
    }

    public function testVoucherHandlersValidateCreateUpdateAndDelete(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::atLeast(1))->method('persist');
        $entityManager->expects(self::atLeast(1))->method('flush');
        $persistence = new DoctrineUnitOfWork($entityManager);
        $repo = $this->voucherRepository();
        $payload = new VoucherPayload($repo);
        $createVoucher = new CreateVoucherHandler($persistence, $payload);
        $updateVoucher = new UpdateVoucherHandler($persistence, $payload);
        $deleteVoucher = new DeleteVoucherHandler($persistence);

        $voucher = $createVoucher->create([
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

        $updated = $updateVoucher->update($voucher, [
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
            $createVoucher->create([
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
            $createVoucher->create([
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
            $updateVoucher->update($updated, [
                'name' => 'Voucher 2',
                'code' => ' test-2 ',
                'discountType' => Voucher::TYPE_PERCENT,
                'discountValue' => 0,
            ]);
            self::fail('Expected discount value exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La valeur de remise doit être supérieure à zéro.', $exception->getMessage());
        }

        $deleteVoucher->delete($updated);
    }

    public function testCreateVoucherRejectsDuplicateCodesFromRepository(): void
    {
        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new \Doctrine\ORM\EntityManager($connection, $config);
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $schemaTool->createSchema([$entityManager->getClassMetadata(Voucher::class)]);

        $registry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        $repo = new \App\Module\Voucher\Infrastructure\Repository\VoucherRepository($registry);
        $manager = new CreateVoucherHandler(new DoctrineUnitOfWork($entityManager), new VoucherPayload($repo));

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
