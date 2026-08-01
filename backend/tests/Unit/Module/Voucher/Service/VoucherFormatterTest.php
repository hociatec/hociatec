<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Service;

use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Service\VoucherFormatter;
use PHPUnit\Framework\TestCase;

final class VoucherFormatterTest extends TestCase
{
    public function testFormatterCannotBeConstructedPublicly(): void
    {
        $reflection = new \ReflectionClass(VoucherFormatter::class);
        $formatter = $reflection->newInstanceWithoutConstructor();
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);
        self::assertFalse($constructor->isPublic());

        $constructor->invoke($formatter);

        self::assertInstanceOf(VoucherFormatter::class, $formatter);
    }

    public function testItFormatsVoucher(): void
    {
        $voucher = new Voucher('Gift', 'gift-10', Voucher::TYPE_FIXED_CENTS, 1000);
        $this->setEntityId($voucher, 88);
        $voucher
            ->setDescription('Desc')
            ->setStartsAt(new \DateTimeImmutable('-1 day'))
            ->setEndsAt(new \DateTimeImmutable('+1 day'))
            ->setRecipientUserId(42)
            ->setRecipientEmail('ada@example.com')
            ->setSentAt(new \DateTimeImmutable('now'));

        $formatted = VoucherFormatter::formatVoucher($voucher);

        self::assertSame(88, $formatted['id']);
        self::assertSame('Gift', $formatted['name']);
        self::assertSame('GIFT-10', $formatted['code']);
        self::assertSame('Desc', $formatted['description']);
        self::assertSame(Voucher::TYPE_FIXED_CENTS, $formatted['discountType']);
        self::assertSame(1000, $formatted['discountValue']);
        self::assertTrue($formatted['isActive']);
        self::assertSame(42, $formatted['recipientUserId']);
        self::assertSame('ada@example.com', $formatted['recipientEmail']);
        self::assertIsString($formatted['createdAt']);
        self::assertIsString($formatted['updatedAt']);
    }

    public function testItFormatsCartVoucherWithoutRecipientOrAuditFields(): void
    {
        $voucher = new Voucher('Gift', 'gift-10', Voucher::TYPE_FIXED_CENTS, 1000);
        $this->setEntityId($voucher, 88);
        $voucher
            ->setDescription('Desc')
            ->setRecipientUserId(42)
            ->setRecipientEmail('ada@example.com')
            ->setSentAt(new \DateTimeImmutable('now'));

        $formatted = VoucherFormatter::formatCartVoucher($voucher, 750);

        self::assertSame([
            'id',
            'name',
            'code',
            'description',
            'discountType',
            'discountValue',
            'discountAmountCents',
        ], array_keys($formatted));
        self::assertSame(750, $formatted['discountAmountCents']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
