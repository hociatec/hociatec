<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\BetaTest\Domain\Enum\BetaCampaignStatus;
use App\Module\BetaTest\Domain\Enum\BetaTesterStatus;
use App\Module\BetaTest\Domain\Exception\BetaTestOperationException;
use App\Module\Catalog\Domain\Entity\ProductSellingType;
use App\Module\Loyalty\Domain\Exception\LoyaltyOperationException;
use App\Module\Notification\Domain\Exception\NotificationOperationException;
use App\Module\Order\Application\DTO\CartOrderSummary;
use App\Module\Order\Application\DTO\InvoiceDocument;
use App\Module\Order\Application\DTO\OrderCreationData;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Http\RequestContext;
use App\Shared\Application\InvoiceIssuerProfile;
use PHPUnit\Framework\TestCase;

final class AdditionalBackendTypesClosureTest extends TestCase
{
    public function testEnumsExceptionsAndReadonlyDataObjects(): void
    {
        self::assertSame(['draft', 'active', 'closed'], BetaCampaignStatus::values());
        self::assertSame(['pending', 'accepted', 'paused', 'rejected'], BetaTesterStatus::values());
        self::assertSame(ProductSellingType::Sale, ProductSellingType::fromInput(' sale '));
        self::assertSame(ProductSellingType::Rental, ProductSellingType::fromInput(ProductSellingType::Rental));

        try {
            ProductSellingType::fromInput('broken');
            self::fail('Expected invalid selling type.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Type de vente/location invalide.', $exception->getMessage());
        }

        $previous = new \RuntimeException('previous');
        self::assertSame($previous, BetaTestOperationException::failed('beta', $previous)->getPrevious());
        self::assertSame($previous, LoyaltyOperationException::failed('loyalty', $previous)->getPrevious());
        self::assertSame($previous, NotificationOperationException::failed('notification', $previous)->getPrevious());

        $invoice = new InvoiceDocument('%PDF', '<xml/>');
        self::assertSame('%PDF', $invoice->pdf);
        self::assertSame('<xml/>', $invoice->xml);

        $user = new User('dto@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $address = new ShippingAddress($user, 'Home', '1 rue A', '75001', 'Paris', 'FR');
        $summary = CartOrderSummary::fromArray([
            'subtotalPriceCents' => 10000,
            'discountAmountCents' => 1500,
            'totalPriceCents' => 8500,
            'appliedVoucher' => ['name' => 'Summer', 'code' => 'SUMMER'],
        ]);
        $creation = new OrderCreationData($user, $address, $summary, new \DateTimeImmutable('2026-08-01T10:00:00+00:00'));
        self::assertSame($user, $creation->user);
        self::assertSame($address, $creation->address);
        self::assertSame('SUMMER', $creation->summary->appliedPromotionSlug);
        self::assertSame('2026-08-01T10:00:00+00:00', $creation->invoicedAt->format(DATE_ATOM));

        self::assertSame('request_id', RequestContext::REQUEST_ID_ATTRIBUTE);
        self::assertSame('Hociatec', InvoiceIssuerProfile::NAME);
        self::assertContains('France', InvoiceIssuerProfile::ADDRESS_LINES);
        self::assertSame('Livraison de biens', InvoiceIssuerProfile::OPERATION_NATURE);
    }
}
