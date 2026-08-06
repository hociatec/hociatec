<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Provider;

use App\Module\Order\Application\DTO\OrderCustomerSnapshot;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;

final readonly class OrderNotificationContextBuilder
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private string $frontendUrl,
    ) {
    }

    /**
     * @param array<string, string> $extraContext
     *
     * @return array<string, scalar|null>
     */
    public function build(Order $order, array $extraContext): array
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $orderId = $order->getId();
        if (null === $orderId) {
            throw new \RuntimeException('La commande doit être persistée pour générer la notification.');
        }
        $quoteNumber = $this->quotes->findConvertedQuoteForOrder($orderId)?->getNumber() ?? '';
        $isPendingPayment = Order::STATUS_PENDING === $order->getStatus();
        $customer = OrderCustomerSnapshot::fromOrder($order);

        return $extraContext + [
            'first_name' => $customer->firstName,
            'last_name' => $customer->lastName,
            'full_name' => $customer->fullName,
            'email' => $customer->email,
            'order_number' => $order->getNumber(),
            'order_status' => $order->getStatus(),
            'order_status_label' => $this->formatStatus($order->getStatus()),
            'order_email_status_title' => $isPendingPayment ? 'en attente de règlement' : 'confirmée',
            'order_payment_instruction' => $isPendingPayment
                ? 'Cette commande est en attente de règlement. Elle sera validée et confirmée uniquement après réception effective du paiement.'
                : 'Cette commande est confirmée. Vous pouvez suivre sa préparation depuis votre espace client.',
            'order_payment_next_step' => $isPendingPayment
                ? 'Pour finaliser la commande, ouvrez le lien ci-dessous puis cliquez sur le bouton de règlement. Une fois le paiement accepté, la commande passera automatiquement au statut confirmé.'
                : 'Aucune action de paiement supplémentaire n’est nécessaire pour cette commande.',
            'quote_number' => $quoteNumber,
            'order_origin_sentence' => '' !== $quoteNumber
                ? 'Cette commande résulte de votre devis numéro '.$quoteNumber.'. Les lignes, quantités et montants repris correspondent au devis accepté.'
                : 'Cette commande a été enregistrée depuis votre espace client.',
            'invoice_number' => $order->getInvoiceNumber() ?? '',
            'invoice_date' => $order->getInvoicedAt()?->format('d/m/Y') ?? '',
            'order_total_eur' => number_format($order->getTotalPriceCents() / 100, 2, ',', ' '),
            'order_created_at' => $order->getCreatedAt()->format('d/m/Y'),
            'billing_name' => (string) ($order->getBillingName() ?? $customer->fullName),
            'app_frontend_url' => $frontendUrl,
            'order_detail_url' => $frontendUrl.'/orders/'.$order->getId(),
            'orders_list_url' => $frontendUrl.'/orders/me',
            'invoice_pdf_url' => $frontendUrl.'/orders/'.$order->getId(),
            'invoice_xml_url' => $frontendUrl.'/orders/'.$order->getId(),
            'purchase_order_number' => (string) ($order->getPurchaseOrderNumber() ?? ''),
        ];
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'en attente',
            Order::STATUS_CONFIRMED => 'confirmée',
            Order::STATUS_DELIVERED => 'livrée',
            Order::STATUS_CANCELLED => 'annulée',
            default => $status,
        };
    }
}
