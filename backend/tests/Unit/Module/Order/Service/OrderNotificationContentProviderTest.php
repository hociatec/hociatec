<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Order\Application\Provider\OrderNotificationContentProvider;
use App\Module\Order\Application\Provider\OrderNotificationContextBuilder;
use App\Module\Order\Application\Provider\OrderNotificationTemplateRenderer;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class OrderNotificationContentProviderTest extends TestCase
{
    public function testBuildUsesFallbackContentAndResolvedContext(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $quotes = $this->createMock(QuoteRepositoryPort::class);
        $provider = new OrderNotificationContentProvider(
            $templates,
            new OrderNotificationContextBuilder($quotes, 'https://front.example.test/'),
            new OrderNotificationTemplateRenderer(),
        );
        $order = $this->order(Order::STATUS_PENDING);
        $quote = new Quote('DEV-2026-0042');

        $templates->expects(self::once())->method('findActiveOneByScenarioKey')->with('order_created')->willReturn(null);
        $quotes->expects(self::once())->method('findConvertedQuoteForOrder')->with(77)->willReturn($quote);

        $content = $provider->build($order, 'order_created', ['custom_value' => 'X']);

        self::assertStringContainsString('Commande ORD-2026-0001 en attente de règlement', $content['subject']);
        self::assertStringContainsString('Ada', $content['html']);
        self::assertStringContainsString('DEV-2026-0042', $content['html']);
        self::assertStringContainsString('1 234,56 EUR', $content['html']);
        self::assertStringContainsString('https://front.example.test/orders/77', $content['html']);
        self::assertStringContainsString('Cette commande est en attente de règlement.', $content['text']);
    }

    public function testBuildUsesCustomTemplateAndEscapesHtmlContext(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $quotes = $this->createMock(QuoteRepositoryPort::class);
        $provider = new OrderNotificationContentProvider(
            $templates,
            new OrderNotificationContextBuilder($quotes, 'https://front.example.test'),
            new OrderNotificationTemplateRenderer(),
        );
        $order = $this->order(Order::STATUS_DELIVERED, '<Ada>');

        $template = new EmailTemplate(
            'Template',
            'template',
            'order_status_delivered',
            'Sujet {{order_status_label}}',
            '<p>{{first_name}} {{custom_html}}</p>',
            'Texte {{order_status_label}} {{custom_html}}'
        );

        $templates->expects(self::once())->method('findActiveOneByScenarioKey')->with('order_status_delivered')->willReturn($template);
        $quotes->expects(self::once())->method('findConvertedQuoteForOrder')->with(77)->willReturn(null);

        $content = $provider->build($order, 'order_status_delivered', ['custom_html' => '<b>unsafe</b>']);

        self::assertSame('Sujet livrée', $content['subject']);
        self::assertSame('<p>&lt;Ada&gt; &lt;b&gt;unsafe&lt;/b&gt;</p>', $content['html']);
        self::assertSame('Texte livrée <b>unsafe</b>', $content['text']);
    }

    public function testBuildCoversInvoiceCancelledAndDefaultFallbackScenarios(): void
    {
        $templates = $this->createMock(EmailTemplateRepository::class);
        $quotes = $this->createMock(QuoteRepositoryPort::class);
        $provider = new OrderNotificationContentProvider(
            $templates,
            new OrderNotificationContextBuilder($quotes, 'https://front.example.test'),
            new OrderNotificationTemplateRenderer(),
        );
        $order = $this->order(Order::STATUS_CANCELLED);
        $order->setInvoiceNumber('FAC-2026-0001')
            ->setInvoicedAt(new \DateTimeImmutable('2026-07-20'))
            ->setBillingEmail('billing@example.com');

        $templates->expects(self::exactly(3))->method('findActiveOneByScenarioKey')->willReturn(null);
        $quotes->expects(self::exactly(3))->method('findConvertedQuoteForOrder')->with(77)->willReturn(null);

        $invoice = $provider->build($order, 'order_invoice_issued');
        self::assertStringContainsString('FAC-2026-0001', $invoice['subject']);
        self::assertStringContainsString('20/07/2026', $invoice['text']);
        self::assertStringContainsString('https://front.example.test/orders/77', $invoice['html']);

        $cancelled = $provider->build($order, 'order_status_cancelled');
        self::assertStringContainsString('annulée', $cancelled['subject']);
        self::assertStringContainsString('annulée', $cancelled['html']);

        $default = $provider->build($order, 'other_status');
        self::assertStringContainsString('Mise à jour de votre commande ORD-2026-0001', $default['subject']);
        self::assertStringContainsString('a été mise à jour', $default['text']);
    }

    public function testUnknownOrderStatusIsRejectedByTheDomainEnum(): void
    {
        $this->expectException(\ValueError::class);
        $this->order('archived');
    }

    private function order(string $status, string $firstName = 'Ada'): Order
    {
        $user = new User('ada@example.com', $firstName, 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $order = new Order('ORD-2026-0001', $user);
        $this->setId($user, 7);
        $this->setId($order, 77);

        $order->setStatus($status)
            ->setTotalPriceCents(123456)
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('10 rue Exemple')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setDeliveryStatus(Order::DELIVERY_STATUS_DELIVERED)
            ->setDeliveryCarrier('Colissimo')
            ->setDeliveryTrackingNumber('TRACK-1')
            ->setDeliveryTrackingUrl('https://carrier.example.test/track/1')
            ->setPurchaseOrderNumber('PO-42');

        return $order;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
