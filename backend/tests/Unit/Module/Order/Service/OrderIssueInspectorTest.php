<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderEvent;
use App\Module\Order\Service\OrderIssueInspector;
use App\Module\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class OrderIssueInspectorTest extends TestCase
{
    public function testItCollectsDefaultOperationalIssuesAndFormatsKnownEvents(): void
    {
        $order = new Order(
            'CMD-2026-0001',
            new User('client@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'f'),
        );

        $events = [
            new OrderEvent($order, 'email_failed', 'SMTP indisponible', null, null),
            new OrderEvent($order, 'invoice_generation_failed', null, null, null),
            new OrderEvent($order, 'post_processing_failed', 'Webhook KO', null, null),
            new OrderEvent($order, 'other', 'Texte libre', null, null),
            new OrderEvent($order, 'other', null, null, null),
        ];

        $issues = OrderIssueInspector::getOperationalIssues($order, $events);

        self::assertSame(
            [
                'Facture PDF non générée',
                'Facture XML non générée',
                'Email de confirmation de commande non envoyé',
                'Échec d’envoi email : SMTP indisponible',
                'Échec de génération de facture',
                'Échec de post-traitement : Webhook KO',
                'Texte libre',
                'Incident technique détecté',
            ],
            $issues,
        );
    }

    public function testItDeduplicatesRepeatedIssuesAndSkipsDefaultsWhenResolved(): void
    {
        $order = new Order(
            'CMD-2026-0002',
            new User('client@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'f'),
        );
        $order
            ->setInvoicePdfPath('/tmp/invoice.pdf')
            ->setInvoiceXmlPath('/tmp/invoice.xml')
            ->setOrderCreatedEmailSentAt(new \DateTimeImmutable('2026-07-29T12:00:00+00:00'));

        $events = [
            new OrderEvent($order, 'email_failed', 'SMTP indisponible', null, null),
            new OrderEvent($order, 'email_failed', 'SMTP indisponible', null, null),
        ];

        self::assertSame(
            ['Échec d’envoi email : SMTP indisponible'],
            OrderIssueInspector::getOperationalIssues($order, $events),
        );
    }

    public function testItFormatsRemainingKnownEventVariants(): void
    {
        $order = new Order(
            'CMD-2026-0003',
            new User('client@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'f'),
        );
        $order
            ->setInvoicePdfPath('/tmp/invoice.pdf')
            ->setInvoiceXmlPath('/tmp/invoice.xml')
            ->setOrderCreatedEmailSentAt(new \DateTimeImmutable('2026-07-29T12:00:00+00:00'));

        $issues = OrderIssueInspector::getOperationalIssues($order, [
            new OrderEvent($order, 'email_failed', null, null, null),
            new OrderEvent($order, 'invoice_generation_failed', 'Filesystem KO', null, null),
        ]);

        self::assertSame(
            [
                'Échec d’envoi email',
                'Échec de génération de facture : Filesystem KO',
            ],
            $issues,
        );
    }
}
