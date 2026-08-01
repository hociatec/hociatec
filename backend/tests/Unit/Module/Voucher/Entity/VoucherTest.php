<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Entity;

use App\Module\User\Entity\User;
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

    public function testRecipientMatchingUsesUserIdAsStableAccountAnchorWhenDefined(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 42);

        self::assertTrue((new Voucher('Public', 'PUBLIC', Voucher::TYPE_FIXED_CENTS, 1000))->matchesRecipient(null));
        self::assertFalse((new Voucher('Private', 'PRIVATE', Voucher::TYPE_FIXED_CENTS, 1000))->setRecipientUserId(42)->matchesRecipient(null));
        self::assertTrue((new Voucher('User', 'USER', Voucher::TYPE_FIXED_CENTS, 1000))->setRecipientUserId(42)->matchesRecipient($user));
        self::assertTrue((new Voucher('Mail', 'MAIL', Voucher::TYPE_FIXED_CENTS, 1000))->setRecipientEmail('ADA@EXAMPLE.COM')->matchesRecipient($user));
        self::assertTrue(
            (new Voucher('Both', 'BOTH', Voucher::TYPE_FIXED_CENTS, 1000))
                ->setRecipientUserId(42)
                ->setRecipientEmail('ada@example.com')
                ->matchesRecipient($user)
        );
        self::assertTrue(
            (new Voucher('Changed mail', 'CHANGEDMAIL', Voucher::TYPE_FIXED_CENTS, 1000))
                ->setRecipientUserId(42)
                ->setRecipientEmail('other@example.com')
                ->matchesRecipient($user)
        );
        self::assertFalse(
            (new Voucher('Bad user', 'BADUSER', Voucher::TYPE_FIXED_CENTS, 1000))
                ->setRecipientUserId(99)
                ->setRecipientEmail('ada@example.com')
                ->matchesRecipient($user)
        );

        self::assertTrue(
            (new Voucher('Notify', 'NOTIFY', Voucher::TYPE_FIXED_CENTS, 1000))
                ->setRecipientUserId(42)
                ->setRecipientEmail('previous@example.com')
                ->canBeNotifiedTo($user, new \DateTimeImmutable('2026-08-01 12:00:00'))
        );
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
