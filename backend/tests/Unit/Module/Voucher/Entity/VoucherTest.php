<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Entity;

use App\Module\Voucher\Entity\Voucher;
use PHPUnit\Framework\TestCase;

final class VoucherTest extends TestCase
{
    public function testVoucherMutatorsAndLifecycle(): void
    {
        $voucher = new Voucher('Gift', 'gift-10', Voucher::TYPE_FIXED_CENTS, 1000);
        $originalUpdatedAt = $voucher->getUpdatedAt();

        self::assertSame('Gift', $voucher->getName());
        self::assertSame('GIFT-10', $voucher->getCode());
        self::assertSame(Voucher::TYPE_FIXED_CENTS, $voucher->getDiscountType());
        self::assertSame(1000, $voucher->getDiscountValue());
        self::assertTrue($voucher->isActive());

        $voucher
            ->setName('Gift 2')
            ->setCode('  promo-20 ')
            ->setDescription('Desc')
            ->setDiscountType(Voucher::TYPE_PERCENT)
            ->setDiscountValue(20)
            ->setIsActive(false)
            ->setStartsAt(new \DateTimeImmutable('+1 day'))
            ->setEndsAt(new \DateTimeImmutable('+2 day'))
            ->setRecipientUserId(42)
            ->setRecipientEmail('Ada@example.com')
            ->setSentAt(new \DateTimeImmutable('+3 day'));

        self::assertSame('Gift 2', $voucher->getName());
        self::assertSame('PROMO-20', $voucher->getCode());
        self::assertSame('Desc', $voucher->getDescription());
        self::assertSame(Voucher::TYPE_PERCENT, $voucher->getDiscountType());
        self::assertSame(20, $voucher->getDiscountValue());
        self::assertFalse($voucher->isActive());
        self::assertSame(42, $voucher->getRecipientUserId());
        self::assertSame('Ada@example.com', $voucher->getRecipientEmail());
        self::assertInstanceOf(\DateTimeImmutable::class, $voucher->getSentAt());

        usleep(1000);
        $voucher->touch();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $voucher->getUpdatedAt());
    }

    public function testVoucherRejectsInvalidDomainStates(): void
    {
        try {
            new Voucher('', 'CODE', Voucher::TYPE_FIXED_CENTS, 1000);
            self::fail('Expected missing name exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le nom du voucher est obligatoire.', $exception->getMessage());
        }

        try {
            new Voucher('Gift', '', Voucher::TYPE_FIXED_CENTS, 1000);
            self::fail('Expected missing code exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le code du voucher est obligatoire.', $exception->getMessage());
        }

        try {
            new Voucher('Gift', 'CODE', 'weird', 1000);
            self::fail('Expected invalid type exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Type de remise invalide.', $exception->getMessage());
        }

        try {
            new Voucher('Gift', 'CODE', Voucher::TYPE_FIXED_CENTS, -1);
            self::fail('Expected invalid value exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La valeur de remise doit être supérieure à zéro.', $exception->getMessage());
        }

        try {
            new Voucher('Gift', 'CODE', Voucher::TYPE_PERCENT, 101);
            self::fail('Expected invalid percent exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La remise en pourcentage ne peut pas dépasser 100 %.', $exception->getMessage());
        }

        $voucher = new Voucher('Gift', 'CODE', Voucher::TYPE_FIXED_CENTS, 1000);

        try {
            $voucher->setEndsAt(new \DateTimeImmutable('2026-07-29 10:00:00'));
            $voucher->setStartsAt(new \DateTimeImmutable('2026-07-29 10:00:00'));
            self::fail('Expected invalid range exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La date de fin doit être postérieure à la date de début.', $exception->getMessage());
        }
    }
}
