<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Application\Text\Slugifier;

final class InvoiceDownloadNameBuilder
{
    use Slugifier;

    public function build(Order $order): string
    {
        $datePart = $order->getInvoicedAt()?->format('Y-m-d') ?? $order->getCreatedAt()->format('Y-m-d');
        $clientName = $this->normalize(trim($order->getUser()->getFirstName().' '.$order->getUser()->getLastName()));
        $orderNumber = $this->normalize($order->getNumber());

        return sprintf('facture-%s-%s-%s', $datePart, $clientName, $orderNumber);
    }

    private function normalize(string $value): string
    {
        return $this->slugifyValue($value, 'client');
    }
}
