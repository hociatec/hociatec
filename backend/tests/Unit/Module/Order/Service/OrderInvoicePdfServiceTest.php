<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Application\Service\OrderInvoicePdfService;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Infrastructure\Pdf\PdfHtmlFormatter;
use PHPUnit\Framework\TestCase;

final class OrderInvoicePdfServiceTest extends TestCase
{
    public function testBuildHtmlRendersLegalMentionsCustomerDataAndTotals(): void
    {
        $service = $this->service();
        $order = $this->order();
        $order
            ->setInvoiceNumber('FAC-2026-0001')
            ->setInvoicedAt(new \DateTimeImmutable('2026-07-20'))
            ->setBillingName('Ada & Co')
            ->setBillingCompany('Ada <Tech>')
            ->setBillingCompanySiren('123456789')
            ->setBillingCompanyVatNumber('FR123456789')
            ->setBillingAddress("10 rue Principale\nBâtiment A")
            ->setBillingPostalCode('75001')
            ->setBillingCity('Paris')
            ->setBillingEmail('billing@example.test')
            ->setPurchaseOrderNumber('PO-42')
            ->setCurrencyCode('eur')
            ->setElectronicFormat('factur-x')
            ->setShippingName('Ada Delivery')
            ->setShippingAddress('20 avenue Livraison')
            ->setShippingPostalCode('69000')
            ->setShippingCity('Lyon');

        $html = $this->buildHtml($service, $order, [
            'subtotalTtcBeforeDiscount' => 150000,
            'totalDiscountTtc' => 5000,
            'totalHt' => 120833,
            'totalVat' => 24167,
            'totalTtc' => 145000,
            'taxBreakdown' => [
                ['rateBps' => 2000, 'taxableCents' => 120833, 'taxCents' => 24167],
            ],
            'items' => [
                [
                    'name' => 'Laptop <Pro>',
                    'sku' => 'SKU-1',
                    'quantity' => 2,
                    'unitPriceHtCents' => 60416,
                    'vatRateBps' => 2000,
                    'lineSubtotalHtCents' => 120833,
                    'lineVatCents' => 24167,
                    'lineTotalTtcCents' => 145000,
                ],
            ],
        ]);

        self::assertStringContainsString('<title>Facture FAC-2026-0001</title>', $html);
        self::assertStringContainsString('Date d\'émission</dt>', $html);
        self::assertStringContainsString('<dd>20/07/2026</dd>', $html);
        self::assertStringContainsString('<dd>19/08/2026</dd>', $html);
        self::assertStringContainsString('Société : Ada &lt;Tech&gt;', $html);
        self::assertStringContainsString('SIREN client : 123456789', $html);
        self::assertStringContainsString('TVA client : FR123456789', $html);
        self::assertStringContainsString('<p>10 rue Principale</p><p>Bâtiment A</p>', $html);
        self::assertStringContainsString('<p>75001 Paris</p>', $html);
        self::assertStringContainsString('Email : billing@example.test', $html);
        self::assertStringContainsString('Téléphone : 0102030405', $html);
        self::assertStringContainsString('Adresse de livraison', $html);
        self::assertStringContainsString('Ada Delivery<br>20 avenue Livraison<br>69000 Lyon', $html);
        self::assertStringContainsString('Laptop &lt;Pro&gt;', $html);
        self::assertStringContainsString('604,16 EUR', $html);
        self::assertStringContainsString('20,00 %', $html);
        self::assertStringContainsString('<strong>1 450,00 EUR</strong>', $html);
        self::assertStringContainsString('934 814 559 00019', $html);
        self::assertStringContainsString('Livraison de biens', $html);
    }

    public function testBuildHtmlFallsBackToUserNameAndOmitsOptionalSections(): void
    {
        $service = $this->service();
        $user = new User('grace@example.test', 'Grace', 'Hopper', new \DateTimeImmutable('1990-01-01'), '   ', 'female');
        $order = new Order('ORD-2', $user);
        $order
            ->setBillingName('   ')
            ->setBillingAddress('1 same road')
            ->setBillingPostalCode('31000')
            ->setBillingCity('Toulouse')
            ->setShippingAddress('1 same road')
            ->setShippingPostalCode('31000')
            ->setShippingCity('Toulouse');

        $html = $this->buildHtml($service, $order, [
            'subtotalTtcBeforeDiscount' => 0,
            'totalDiscountTtc' => 0,
            'totalHt' => 0,
            'totalVat' => 0,
            'totalTtc' => 0,
            'taxBreakdown' => [],
            'items' => [],
        ]);

        self::assertStringContainsString('<strong>Grace Hopper</strong>', $html);
        self::assertStringContainsString('<p>Client : particulier</p>', $html);
        self::assertStringContainsString('<dd>-</dd>', $html);
        self::assertStringNotContainsString('Adresse de livraison', $html);
        self::assertStringNotContainsString('Téléphone :', $html);
        self::assertStringNotContainsString('SIREN client', $html);
        self::assertStringContainsString('<dd>-</dd>', $html);
        self::assertStringContainsString('Bon de commande</dt>', $html);
        self::assertStringContainsString('Format électronique</dt>', $html);
        self::assertStringContainsString('EUR', $html);
    }

    public function testRenderPropagatesRendererFailureWhenAccessiblePdfIsUnavailable(): void
    {
        $service = $this->service();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WeasyPrint n\'est pas installé pour la génération PDF accessible.');

        $service->render($this->order(), [
            'subtotalTtcBeforeDiscount' => 0,
            'totalDiscountTtc' => 0,
            'totalHt' => 0,
            'totalVat' => 0,
            'totalTtc' => 0,
            'taxBreakdown' => [],
            'items' => [],
        ]);
    }

    private function service(): OrderInvoicePdfService
    {
        return new OrderInvoicePdfService(
            new AccessiblePdfRenderer('/tmp/no-backend-project', '', ''),
            new PdfHtmlFormatter(),
        );
    }

    /** @param array<string, mixed> $totals */
    private function buildHtml(OrderInvoicePdfService $service, Order $order, array $totals): string
    {
        $reflection = new \ReflectionObject($service);
        $method = $reflection->getMethod('buildHtml');
        $method->setAccessible(true);

        return $method->invoke($service, $order, $totals);
    }

    private function order(): Order
    {
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');

        return new Order('ORD-2026-0001', $user);
    }
}
