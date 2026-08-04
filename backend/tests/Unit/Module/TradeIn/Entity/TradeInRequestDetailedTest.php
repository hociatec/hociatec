<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Entity;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class TradeInRequestDetailedTest extends TestCase
{
    public function testTradeInRequestLifecycleAndMutators(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $request = new TradeInRequest(
            'TR-1',
            $user,
            'Ada',
            'Lovelace',
            'ada@example.com',
            '0102030405',
            'smartphones',
            'iPhone',
            -100,
            2023,
            'Apple',
            '13',
            'SN-1',
            'A',
            true,
            true,
            false,
            'Bon etat',
            10,
            'iPhone 13',
            -50,
            80,
            new \DateTimeImmutable('2026-07-01T10:00:00+00:00'),
        );
        $updatedAt = $request->getUpdatedAt();

        self::assertNull($request->getId());
        self::assertSame('TR-1', $request->getReference());
        self::assertSame($user, $request->getUser());
        self::assertSame('Ada', $request->getFirstName());
        self::assertSame('Lovelace', $request->getLastName());
        self::assertSame('ada@example.com', $request->getEmail());
        self::assertSame('0102030405', $request->getPhone());
        self::assertSame('Ada Lovelace', $request->applicant()->fullName());
        self::assertSame('smartphones', $request->getCategory());
        self::assertSame('iPhone', $request->getProductName());
        self::assertSame(0, $request->getPurchasePriceCents());
        self::assertSame(2023, $request->getPurchaseYear());
        self::assertSame('Apple', $request->getBrand());
        self::assertSame('13', $request->getModel());
        self::assertSame('SN-1', $request->getSerialNumber());
        self::assertSame('A', $request->getConditionGrade());
        self::assertTrue($request->isFunctional());
        self::assertTrue($request->hasAccessories());
        self::assertFalse($request->hasProofOfPurchase());
        self::assertSame('Bon etat', $request->getDescription());
        self::assertSame(10, $request->getCatalogProductId());
        self::assertSame('iPhone 13', $request->getCatalogProductName());
        self::assertSame('iPhone', $request->productSnapshot()->productName);
        self::assertTrue($request->productSnapshot()->functional);
        self::assertSame(0, $request->getEstimatedMinCents());
        self::assertSame(80, $request->getEstimatedMaxCents());
        self::assertSame(80, $request->estimate()->maxCents);
        self::assertNull($request->getOfferCents());
        self::assertNull($request->getFinalOfferCents());
        self::assertNull($request->getPaymentMethod());
        self::assertSame('pending', $request->getPaymentStatus());
        self::assertNull($request->getTransactionReference());
        self::assertNull($request->getPaidAt());
        self::assertNull($request->getRibPath());
        self::assertNull($request->getRibOriginalName());
        self::assertNull($request->getRibSize());
        self::assertNull($request->getReceiptPath());
        self::assertNull($request->getVoucherCode());
        self::assertNull($request->getClosedAt());
        self::assertNull($request->getAdminNote());
        self::assertSame(TradeInStatus::SUBMITTED, $request->getStatus());
        self::assertSame('2026-07-01T10:00:00+00:00', $request->getConsentAt()->format(DATE_ATOM));
        self::assertNull($request->getOfferExpiresAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $request->getCreatedAt());

        usleep(1000);
        $request->setStatus(TradeInStatus::UNDER_REVIEW);
        self::assertSame(TradeInStatus::UNDER_REVIEW, $request->getStatus());
        self::assertGreaterThan($updatedAt, $request->getUpdatedAt());

        $request->setOffer(-10, new \DateTimeImmutable('2026-08-01T10:00:00+00:00'));
        self::assertSame(0, $request->getOfferCents());
        self::assertSame('2026-08-01T10:00:00+00:00', $request->getOfferExpiresAt()?->format(DATE_ATOM));

        $request->setClosure(250, ' bank_transfer ', ' paid ', '  TX-1  ', new \DateTimeImmutable('2026-07-15T10:00:00+00:00'));
        self::assertSame(250, $request->getFinalOfferCents());
        self::assertSame('bank_transfer', $request->getPaymentMethod());
        self::assertSame('paid', $request->getPaymentStatus());
        self::assertSame('TX-1', $request->getTransactionReference());
        self::assertSame('2026-07-15T10:00:00+00:00', $request->getPaidAt()?->format(DATE_ATOM));
        self::assertSame('bank_transfer', $request->closure()?->paymentMethod);
        self::assertInstanceOf(\DateTimeImmutable::class, $request->getClosedAt());

        $request
            ->setRib('/tmp/rib.pdf', 'rib.pdf', 1234, 'hash')
            ->setReceiptPath('/tmp/receipt.pdf')
            ->setVoucherCode(' CODE ')
            ->setAdminNote(' note ');

        self::assertSame('/tmp/rib.pdf', $request->getRibPath());
        self::assertSame('rib.pdf', $request->getRibOriginalName());
        self::assertSame(1234, $request->getRibSize());
        self::assertSame('hash', $request->ribDocument()?->sha256);
        self::assertSame('/tmp/receipt.pdf', $request->getReceiptPath());
        self::assertSame('/tmp/receipt.pdf', $request->receiptDocument()?->path);
        self::assertSame('CODE', $request->getVoucherCode());
        self::assertSame('note', $request->getAdminNote());

        $request->setVoucherCode('   ')->setAdminNote(null)->setOffer(null);
        self::assertNull($request->getVoucherCode());
        self::assertNull($request->getAdminNote());
        self::assertNull($request->getOfferCents());
        self::assertNull($request->getOfferExpiresAt());

        self::assertSame(
            ['submitted', 'under_review', 'offer_sent', 'accepted', 'declined', 'received', 'inspected', 'completed', 'cancelled', 'expired'],
            array_map(static fn (TradeInStatus $status): string => $status->value, TradeInStatus::cases()),
        );
    }
}
