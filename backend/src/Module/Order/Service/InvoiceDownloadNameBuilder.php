<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;

final class InvoiceDownloadNameBuilder
{
    public function build(Order $order): string
    {
        $datePart = $order->getInvoicedAt()?->format('Y-m-d') ?? $order->getCreatedAt()->format('Y-m-d');
        $clientName = $this->normalize(trim($order->getUser()->getFirstName() . ' ' . $order->getUser()->getLastName()));
        $orderNumber = $this->normalize($order->getNumber());

        return sprintf('facture-%s-%s-%s', $datePart, $clientName, $orderNumber);
    }

    private function normalize(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = $ascii !== false ? $ascii : $value;
        $normalized = strtolower($normalized);
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '-', $normalized);
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'client';
    }
}
