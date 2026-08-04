<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Promotion\Service;

use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Application\Service\PromotionManager;
use App\Infrastructure\Persistence\DoctrinePersistence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PromotionManagerAdditionalTest extends TestCase
{
    public function testCreateUpdateAndDeleteDelegatePersistenceAndApplyOptionalFields(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Promotion::class));
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(Promotion::class));
        $entityManager->expects(self::exactly(3))->method('flush');

        $manager = new PromotionManager(new DoctrinePersistence($entityManager));
        $created = $manager->create(PromotionInput::fromArray([
            'name' => '  Rentree  ',
            'slug' => ' rentree ',
            'discountType' => Promotion::TYPE_PERCENT,
            'discountValue' => '15',
            'audienceKey' => 'all_users',
            'criteria' => ['minimumCartTotalCents' => 5000],
            'description' => '  Promotion active  ',
            'isActive' => true,
            'startsAt' => '2026-08-01T00:00:00+00:00',
            'endsAt' => '2026-08-31T23:59:59+00:00',
        ]));

        self::assertSame('Rentree', $created->getName());
        self::assertSame('rentree', $created->getSlug());
        self::assertSame('Promotion active', $created->getDescription());
        self::assertSame(Promotion::TYPE_PERCENT, $created->getDiscountType());
        self::assertSame(15, $created->getDiscountValue());
        self::assertSame('all_users', $created->getAudienceKey());
        self::assertSame(['minimumCartTotalCents' => 5000], $created->getCriteria());
        self::assertTrue($created->isActive());
        self::assertSame('2026-08-01', $created->getStartsAt()?->format('Y-m-d'));
        self::assertSame('2026-08-31', $created->getEndsAt()?->format('Y-m-d'));

        $updated = $manager->update($created, new PromotionInput(
            'Black Friday',
            'black-friday',
            Promotion::TYPE_FIXED_CENTS,
            2500,
            'loyal_customers',
            ['minimumOrders' => 3],
            null,
            false,
            null,
        ));

        self::assertSame($created, $updated);
        self::assertSame('Black Friday', $updated->getName());
        self::assertNull($updated->getDescription());
        self::assertSame(Promotion::TYPE_FIXED_CENTS, $updated->getDiscountType());
        self::assertSame(2500, $updated->getDiscountValue());
        self::assertSame('loyal_customers', $updated->getAudienceKey());
        self::assertSame(['minimumOrders' => 3], $updated->getCriteria());
        self::assertFalse($updated->isActive());
        self::assertNull($updated->getStartsAt());
        self::assertNull($updated->getEndsAt());

        $manager->delete($updated);
    }
}
