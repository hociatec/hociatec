<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Provider;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Order\Domain\Entity\Order;

final class OrderNotificationContentProvider
{
    public function __construct(
        private readonly EmailTemplateRepositoryPort $templates,
        private readonly OrderNotificationContextBuilder $contextBuilder,
        private readonly OrderNotificationTemplateRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, string> $extraContext
     *
     * @return array<string, string>
     */
    public function build(Order $order, string $scenarioKey, array $extraContext = []): array
    {
        $template = $this->templates->findActiveOneByScenarioKey($scenarioKey);
        $fallback = $this->fallbackTemplateForScenario($scenarioKey);
        $context = $this->contextBuilder->build($order, $extraContext);

        return [
            'subject' => $this->renderer->render($template?->getSubjectTemplate() ?? $fallback['subject'], $context, false),
            'html' => $this->renderer->render($template?->getHtmlBody() ?? $fallback['html'], $context, true),
            'text' => $this->renderer->render($template?->getTextBody() ?? $fallback['text'], $context, false),
        ];
    }

    /** @return array{subject:string, html:string, text:string} */
    private function fallbackTemplateForScenario(string $scenarioKey): array
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
}
