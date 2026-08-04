<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;

final readonly class OrderNotificationContentProvider
{
    public function __construct(
        private EmailTemplateRepository $templates,
        private QuoteRepositoryPort $quotes,
        private string $frontendUrl,
    ) {
    }

    /** @return array{subject:string, html:string, text:string} */
    /**
     * @param array<string, string> $extraContext
     *
     * @return array<string, string>
     */
    public function build(Order $order, string $scenarioKey, array $extraContext = []): array
    {
        $template = $this->templates->findActiveOneByScenarioKey($scenarioKey);
        $fallback = $this->fallback($scenarioKey);
        $context = $this->context($order, $extraContext);

        return [
            'subject' => $this->render($template?->getSubjectTemplate() ?? $fallback['subject'], $context, false),
            'html' => $this->render($template?->getHtmlBody() ?? $fallback['html'], $context, true),
            'text' => $this->render($template?->getTextBody() ?? $fallback['text'], $context, false),
        ];
    }

    /**
     * @param array<string, string> $extraContext
     *
     * @return array<string, scalar|null>
     */
    private function context(Order $order, array $extraContext): array
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $quoteNumber = $this->quotes->findConvertedQuoteForOrder($order)?->getNumber() ?? '';
        $isPendingPayment = Order::STATUS_PENDING === $order->getStatus();

        return $extraContext + [
            'first_name' => $order->getUser()->getFirstName(),
            'last_name' => $order->getUser()->getLastName(),
            'full_name' => $order->getUser()->getFullName(),
            'email' => $order->getUser()->getEmail(),
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
            'billing_name' => (string) ($order->getBillingName() ?? $order->getUser()->getFullName()),
            'app_frontend_url' => $frontendUrl,
            'order_detail_url' => $frontendUrl.'/orders/'.$order->getId(),
            'orders_list_url' => $frontendUrl.'/orders/me',
            'invoice_pdf_url' => $frontendUrl.'/orders/'.$order->getId(),
            'invoice_xml_url' => $frontendUrl.'/orders/'.$order->getId(),
            'purchase_order_number' => (string) ($order->getPurchaseOrderNumber() ?? ''),
        ];
    }

    /** @return array{subject:string, html:string, text:string} */
    private function fallback(string $scenarioKey): array
    {
        return match ($scenarioKey) {
            'order_created' => [
                'subject' => 'Commande {{order_number}} {{order_email_status_title}}',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> a bien été enregistrée pour un montant total de <strong>{{order_total_eur}} EUR</strong>.</p><p>{{order_origin_sentence}}</p><p>{{order_payment_instruction}}</p><p>{{order_payment_next_step}}</p><p>Accès commande : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} a bien été enregistrée pour un montant total de {{order_total_eur}} EUR.\n\n{{order_origin_sentence}}\n\n{{order_payment_instruction}}\n\n{{order_payment_next_step}}\n\nAccès commande : {{order_detail_url}}",
            ],
            'order_invoice_issued' => [
                'subject' => 'Votre facture {{invoice_number}} est disponible',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre facture <strong>{{invoice_number}}</strong> du {{invoice_date}} est maintenant disponible.</p><p>Retrouvez-la depuis le détail de votre commande : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre facture {{invoice_number}} du {{invoice_date}} est maintenant disponible.\n\nAccès commande : {{order_detail_url}}",
            ],
            'order_status_delivered' => [
                'subject' => 'Commande {{order_number}} livrée',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> est marquée comme <strong>{{order_status_label}}</strong>.</p><p>Consultez votre espace client : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} est marquée comme {{order_status_label}}.\n\nDétail : {{order_detail_url}}",
            ],
            'order_status_cancelled' => [
                'subject' => 'Commande {{order_number}} annulée',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> est désormais <strong>{{order_status_label}}</strong>.</p><p>Consultez le détail : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} est désormais {{order_status_label}}.\n\nDétail : {{order_detail_url}}",
            ],
            default => [
                'subject' => 'Mise à jour de votre commande {{order_number}}',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> a été mise à jour.</p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} a été mise à jour.",
            ],
        };
    }

    /** @param array<string, scalar|null> $context */
    private function render(?string $template, array $context, bool $allowHtml): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $renderedValue = (string) ($value ?? '');
            $replacements['{{'.$key.'}}'] = $allowHtml
                ? htmlspecialchars($renderedValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $renderedValue;
        }

        return strtr((string) $template, $replacements);
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
