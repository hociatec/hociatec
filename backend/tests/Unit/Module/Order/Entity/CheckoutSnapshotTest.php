<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Entity;

use App\Module\Order\Domain\Entity\CheckoutBillingSnapshot;
use App\Module\Order\Domain\Entity\CheckoutShippingSnapshot;
use App\Module\Order\Domain\ValueObject\CheckoutShippingAddress;
use PHPUnit\Framework\TestCase;

final class CheckoutSnapshotTest extends TestCase
{
    public function testShippingSnapshotUsesGroupedShippingAddressValueObject(): void
    {
        $snapshot = new CheckoutShippingSnapshot(
            new CheckoutShippingAddress(
                'Ada Lovelace',
                '1 rue de Paris',
                '75001',
                'Paris',
            ),
        );

        self::assertSame('Ada Lovelace', $snapshot->name());
        self::assertSame('1 rue de Paris', $snapshot->address());
        self::assertSame('75001', $snapshot->postalCode());
        self::assertSame('Paris', $snapshot->city());
    }

    public function testBillingSnapshotUsesGroupedBillingAddressValueObject(): void
    {
        $snapshot = CheckoutBillingSnapshot::fromScalars(
            'Ada',
            'OpenAI',
            'FR123',
            'VAT',
            'PO-1',
            'ada@example.com',
            '2 avenue',
            '69000',
            'Lyon',
        );

        self::assertSame('Ada', $snapshot->name());
        self::assertSame('OpenAI', $snapshot->company());
        self::assertSame('FR123', $snapshot->companySiren());
        self::assertSame('VAT', $snapshot->companyVatNumber());
        self::assertSame('PO-1', $snapshot->purchaseOrderNumber());
        self::assertSame('ada@example.com', $snapshot->email());
        self::assertSame('2 avenue', $snapshot->address());
        self::assertSame('69000', $snapshot->postalCode());
        self::assertSame('Lyon', $snapshot->city());
    }
}
