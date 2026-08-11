<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class TradeInNotificationMessageBuilder
{
    public function __construct(
        #[Autowire('%env(APP_FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {
    }

    /**
     * @param array<string, string> $extraContext
     *
     * @return array<string, string>
     */
    public function context(TradeInRequest $request, array $extraContext): array
    {
        return [
            'customer_name' => trim($request->getFirstName().' '.$request->getLastName()),
            'trade_in_reference' => $request->getReference(),
            'trade_in_product' => $this->productLabel($request),
            'trade_in_estimate' => $this->estimateLabel($request),
            'trade_in_status' => $this->statusLabel($request->getStatus()),
            'trade_in_tracking_url' => $this->trackingUrl($request),
        ] + $extraContext;
    }

    /**
     * @return array{subject: string, html: string, text: string}
     */
    public function fallback(string $scenario): array
    {
        if ('trade_in_created' === $scenario) {
            return [
                'subject' => 'Votre demande de reprise {{trade_in_reference}} a bien été reçue',
                'html' => '<p>Bonjour {{customer_name}},</p><p>Votre demande de reprise pour <strong>{{trade_in_product}}</strong> a bien été reçue.</p><p>Référence : <strong>{{trade_in_reference}}</strong><br>Estimation indicative : <strong>{{trade_in_estimate}}</strong></p><p>Suivre votre demande : <a href="{{trade_in_tracking_url}}">{{trade_in_tracking_url}}</a></p><p>L’équipe Hociatec</p>',
                'text' => "Bonjour {{customer_name}},\n\nVotre demande de reprise pour {{trade_in_product}} a bien été reçue.\nRéférence : {{trade_in_reference}}\nEstimation indicative : {{trade_in_estimate}}\n\nSuivi : {{trade_in_tracking_url}}\n\nL’équipe Hociatec",
            ];
        }

        return [
            'subject' => 'Mise à jour de votre reprise {{trade_in_reference}} : {{trade_in_status}}',
            'html' => '<p>Bonjour {{customer_name}},</p><p>Le statut de votre demande <strong>{{trade_in_reference}}</strong> est maintenant : <strong>{{trade_in_status}}</strong>.</p><p>{{trade_in_offer_text}}</p><p>Suivre votre demande : <a href="{{trade_in_tracking_url}}">{{trade_in_tracking_url}}</a></p><p>L’équipe Hociatec</p>',
            'text' => "Bonjour {{customer_name}},\n\nLe statut de votre demande {{trade_in_reference}} est maintenant : {{trade_in_status}}.\n{{trade_in_offer_text}}\n\nSuivi : {{trade_in_tracking_url}}\n\nL’équipe Hociatec",
        ];
    }

    public function internalMessage(TradeInRequest $request): string
    {
        return 'Votre demande de reprise '.$request->getReference().' est à l’état : '.$this->statusLabel($request->getStatus()).'.';
    }

    private function trackingUrl(TradeInRequest $request): string
    {
        return rtrim($this->frontendUrl, '/').(null !== $request->getUser() ? '/reprises' : '/reprise');
    }

    private function productLabel(TradeInRequest $request): string
    {
        return trim(implode(' ', array_filter([$request->getBrand(), $request->getProductName(), $request->getModel()])));
    }

    public function estimateLabel(TradeInRequest $request): string
    {
        return $this->formatCents($request->getEstimatedMinCents()).' à '.$this->formatCents($request->getEstimatedMaxCents());
    }

    public function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    private function statusLabel(TradeInStatus $status): string
    {
        return $status->label();
    }
}
