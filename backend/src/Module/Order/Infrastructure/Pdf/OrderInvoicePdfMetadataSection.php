<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfMetadataSection
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    public function build(Order $order): string
    {
        $customer = (new OrderInvoicePdfCustomerBlock($this->formatter))->build($order);
        $issuedAt = $this->formatter->date($order->getInvoicedAt()?->format('Y-m-d'), true);
        $saleDate = $this->formatter->date($order->getCreatedAt()->format('Y-m-d'), true);
        $deliveryDate = $this->formatter->date($order->getCreatedAt()->format('Y-m-d'), true);
        $dueAt = $this->formatter->date($order->getInvoicedAt()?->modify('+30 days')->format('Y-m-d'), true);
        $orderNumber = $this->formatter->escape($order->getNumber());

        return <<<HTML
<section class="section-card">
  <h2>Mentions obligatoires</h2>
  <dl class="meta-list">
    <dt>Date d'émission</dt><dd>{$issuedAt}</dd>
    <dt>Date de vente</dt><dd>{$saleDate}</dd>
    <dt>Date de livraison</dt><dd>{$deliveryDate}</dd>
    <dt>Date d'échéance</dt><dd>{$dueAt}</dd>
    <dt>Nature de l'opération</dt><dd>{$this->formatter->escape(OrderInvoiceIssuerProfile::OPERATION_NATURE)}</dd>
    <dt>Référence de commande</dt><dd>{$orderNumber}</dd>
    <dt>Bon de commande</dt><dd>{$this->formatter->escape($order->getPurchaseOrderNumber() ?? '-')}</dd>
    <dt>Devise</dt><dd>{$this->formatter->escape($order->getCurrencyCode())}</dd>
    <dt>Format électronique</dt><dd>{$this->formatter->escape($order->getElectronicFormat())}</dd>
    {$customer['delivery']}
  </dl>
</section>
HTML;
    }
}
