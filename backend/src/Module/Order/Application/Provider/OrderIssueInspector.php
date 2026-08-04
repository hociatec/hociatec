<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Provider;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;

final class OrderIssueInspector
{
    /**
     * @param list<OrderEvent> $events
     *
     * @return list<string>
     */
    public static function getOperationalIssues(Order $order, array $events = []): array
    {
        $issues = [];

        if (null === $order->getInvoicePdfPath()) {
            $issues[] = 'Facture PDF non générée';
        }

        if (null === $order->getInvoiceXmlPath()) {
            $issues[] = 'Facture XML non générée';
        }

        if (null === $order->getOrderCreatedEmailSentAt()) {
            $issues[] = 'Email de confirmation de commande non envoyé';
        }

        foreach ($events as $event) {
            $issues[] = self::formatIssueEvent($event);
        }

        return array_values(array_unique($issues));
    }

    private static function formatIssueEvent(OrderEvent $event): string
    {
        $message = trim((string) ($event->getMessage() ?? ''));

        return match ($event->getType()) {
            'email_failed' => '' !== $message
                ? 'Échec d’envoi email : '.$message
                : 'Échec d’envoi email',
            'invoice_generation_failed' => '' !== $message
                ? 'Échec de génération de facture : '.$message
                : 'Échec de génération de facture',
            'post_processing_failed' => '' !== $message
                ? 'Échec de post-traitement : '.$message
                : 'Échec de post-traitement',
            default => '' !== $message ? $message : 'Incident technique détecté',
        };
    }
}
