<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Service\InvoiceDownloadNameBuilder;
use App\Module\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class InvoiceDownloadNameBuilderTest extends TestCase
{
    public function testItBuildsAFilenameFromInvoiceDateCustomerNameAndOrderNumber(): void
    {
        $order = $this->order('CMD-2026-0042', 'Élodie', 'Dupré');
        $order->setInvoicedAt(new \DateTimeImmutable('2026-07-15 10:00:00'));

        $name = (new InvoiceDownloadNameBuilder())->build($order);

        self::assertSame('facture-2026-07-15-elodie-dupre-cmd-2026-0042', $name);
    }

    public function testItFallsBackToCreatedDateAndClientWhenNormalizationIsEmpty(): void
    {
        $order = $this->order('***', '###', '!!!');

        $name = (new InvoiceDownloadNameBuilder())->build($order);

        self::assertStringStartsWith('facture-', $name);
        self::assertStringEndsWith('-client-client', $name);
    }

    private function order(string $number, string $firstName, string $lastName): Order
    {
        $user = new User(
            'client@example.com',
            $firstName,
            $lastName,
            new \DateTimeImmutable('1990-01-01'),
            '0102030405',
            'm',
        );

        return new Order($number, $user);
    }
}
