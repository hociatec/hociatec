<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Entity;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserEntitiesTest extends TestCase
{
    public function testUserNormalizesAndExposesCoreFields(): void
    {
        $user = new User('  Ada@Example.COM ', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $originalUpdatedAt = $user->getUpdatedAt();

        self::assertSame('ada@example.com', $user->getEmail());
        self::assertSame('1990-01-01', $user->getBirthDate()->format('Y-m-d'));
        self::assertSame('ada@example.com', $user->getUserIdentifier());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame('Ada', $user->getFirstName());
        self::assertSame('Lovelace', $user->getLastName());
        self::assertSame('Ada Lovelace', $user->getFullName());
        self::assertSame('0102030405', $user->getPhoneNumber());
        self::assertSame('female', $user->getGender());
        self::assertFalse($user->isVerified());

        $user
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN'])
            ->setPassword('hashed')
            ->setFirstName('Grace')
            ->setLastName('Hopper')
            ->setEmail('  Grace@Example.COM ')
            ->setBirthDate(new \DateTimeImmutable('1980-12-01'))
            ->setPhoneNumber('0607080910')
            ->setGender('other')
            ->setIsVerified(true)
            ->setAdminNotes('  note interne  ')
            ->setAdminTags([' vip ', '', 'b2b', 'vip'])
            ->setLoyaltyPointsBalance(-10)
            ->addLoyaltyPoints(5)
            ->addLoyaltyPoints(-10)
            ->setAccountNotificationsSeenSignature('  sig-1  ')
            ->setCommunicationPreferences(['email', 'phone', 'invalid', 'email'])
            ->setVerificationToken('verify-token')
            ->setVerificationTokenExpiresAt(new \DateTimeImmutable('+1 day'))
            ->setPasswordResetToken('reset-token')
            ->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+2 day'));

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame('hashed', $user->getPassword());
        self::assertSame('Grace', $user->getFirstName());
        self::assertSame('Hopper', $user->getLastName());
        self::assertSame('grace@example.com', $user->getEmail());
        self::assertSame('1980-12-01', $user->getBirthDate()->format('Y-m-d'));
        self::assertSame('0607080910', $user->getPhoneNumber());
        self::assertSame('other', $user->getGender());
        self::assertTrue($user->isVerified());
        self::assertSame('note interne', $user->getAdminNotes());
        self::assertSame(['vip', 'b2b'], $user->getAdminTags());
        self::assertSame(0, $user->getLoyaltyPointsBalance());
        self::assertSame('sig-1', $user->getAccountNotificationsSeenSignature());
        self::assertSame(['email', 'phone'], $user->getCommunicationPreferences());
        self::assertSame('verify-token', $user->getVerificationToken());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getVerificationTokenExpiresAt());
        self::assertSame('reset-token', $user->getPasswordResetToken());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getPasswordResetTokenExpiresAt());

        $user->onPrePersist();
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());

        usleep(1000);
        $user->onPreUpdate();
        self::assertGreaterThanOrEqual($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testUserDefaultsAndNullableFields(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        self::assertSame(['notification', 'email'], $user->getCommunicationPreferences());
        $user->eraseCredentials();

        $user
            ->setAdminNotes('   ')
            ->setAccountNotificationsSeenSignature('   ')
            ->setCommunicationPreferences(['invalid'])
            ->setVerificationToken(null)
            ->setVerificationTokenExpiresAt(null)
            ->setPasswordResetToken(null)
            ->setPasswordResetTokenExpiresAt(null);

        self::assertSame('', $user->getAdminNotes());
        self::assertNull($user->getAccountNotificationsSeenSignature());
        self::assertSame([], $user->getCommunicationPreferences());
        self::assertNull($user->getVerificationToken());
        self::assertNull($user->getVerificationTokenExpiresAt());
        self::assertNull($user->getPasswordResetToken());
        self::assertNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testUserIdentifierRejectsMissingEmail(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        $reflection = new \ReflectionObject($user);
        $email = $reflection->getProperty('email');
        $email->setValue($user, '');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A persisted user must have an email address.');

        $user->getUserIdentifier();
    }

    public function testShippingAddressMutators(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $address = new ShippingAddress($user, 'Ada Lovelace', '1 rue de Paris', '75001', 'Paris');

        $address
            ->setName('Grace Hopper')
            ->setAddress('2 avenue de Lyon')
            ->setPostalCode('69000')
            ->setCity('Lyon')
            ->setCompany('OpenAI')
            ->setCompanySiren('123456789')
            ->setCompanyVatNumber('FR123456789')
            ->setPurchaseOrderNumber('PO-42')
            ->setIsDefault(true);

        self::assertSame($user, $address->getUser());
        self::assertSame('Grace Hopper', $address->getName());
        self::assertSame('2 avenue de Lyon', $address->getAddress());
        self::assertSame('69000', $address->getPostalCode());
        self::assertSame('Lyon', $address->getCity());
        self::assertNull($address->getId());
        self::assertSame('OpenAI', $address->getCompany());
        self::assertSame('123456789', $address->getCompanySiren());
        self::assertSame('FR123456789', $address->getCompanyVatNumber());
        self::assertSame('PO-42', $address->getPurchaseOrderNumber());
        self::assertTrue($address->isDefault());
    }
}
