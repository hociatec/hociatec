<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Provider;

use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Domain\Entity\Quote;

final readonly class QuoteCreatedEmailContentProvider
{
    public function __construct(
        private QuoteCalculator $calculator,
        private EmailTemplateRenderer $templates,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    /** @return array{subject:string, text:string, html:string} */
    public function build(Quote $quote): array
    {
        $totals = $this->calculator->computeTotals($quote);
        $customerName = trim((string) $quote->getCustomerName()) ?: 'Bonjour';
        $frontendUrl = rtrim($this->frontendUrl, '/');

        return $this->templates->renderScenario('quote_created', [
            'customer_name' => $customerName,
            'quote_number' => $quote->getNumber(),
            'quote_total_eur' => number_format($totals['totalTtc'] / 100, 2, ',', ' '),
            'quote_valid_until' => $quote->getValidUntil()?->format('d/m/Y') ?? '',
            'quote_detail_url' => $frontendUrl.'/quotes/me/'.$quote->getId(),
            'app_frontend_url' => $frontendUrl,
            'mailer_from' => $this->mailerFrom,
        ], [
            'subject' => 'Votre devis {{quote_number}} a bien été créé',
            'html' => '<p>Bonjour {{customer_name}},</p><p>Votre devis a bien été créé par <strong>Hociatec</strong>.</p><p>Référence du devis : <strong>{{quote_number}}</strong>.</p><p>Montant total TTC : <strong>{{quote_total_eur}} EUR</strong>.</p><p>Date de validité : <strong>{{quote_valid_until}}</strong>.</p><p>Vous pouvez le consulter depuis votre espace client : <a href="{{quote_detail_url}}">{{quote_detail_url}}</a></p><p>Pensez à vérifier les éléments du devis et à revenir vers nous si vous souhaitez un ajustement.</p><p>Cordialement,<br>L’équipe Hociatec<br>{{mailer_from}}</p>',
            'text' => "Bonjour {{customer_name}},\n\nVotre devis a bien été créé par Hociatec.\nRéférence du devis : {{quote_number}}.\nMontant total TTC : {{quote_total_eur}} EUR.\nDate de validité : {{quote_valid_until}}.\n\nVous pouvez le consulter depuis votre espace client : {{quote_detail_url}}\n\nPensez à vérifier les éléments du devis et à revenir vers nous si vous souhaitez un ajustement.\n\nCordialement,\nL’équipe Hociatec\n{{mailer_from}}",
        ]);
    }
}
