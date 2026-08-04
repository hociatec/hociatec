<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Service;

use App\Module\Marketing\Application\Service\EmailTemplateRenderer;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class TradeInNotificationEmailService
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private UserCommunicationNotifier $userNotifications,
        private string $mailerFrom,
        private string $frontendUrl,
    ) {
    }

    public function sendCreated(TradeInRequest $request): void
    {
        $this->send($request, 'trade_in_created', [
            'trade_in_estimate' => $this->estimateLabel($request),
        ]);
    }

    public function sendStatusChanged(TradeInRequest $request): void
    {
        $offer = null !== $request->getOfferCents() ? $this->formatCents($request->getOfferCents()) : '';
        $this->send($request, 'trade_in_status_changed', [
            'trade_in_offer_block' => '' !== $offer ? 'Offre définitive proposée : '.$offer.'.' : '',
            'trade_in_offer_text' => '' !== $offer ? 'Offre définitive proposée : '.$offer.'.' : '',
        ]);
    }

    /** @param array<string, string> $extraContext */
    private function send(TradeInRequest $request, string $scenario, array $extraContext): void
    {
        $name = trim($request->getFirstName().' '.$request->getLastName());
        $trackingUrl = rtrim($this->frontendUrl, '/').(null !== $request->getUser() ? '/reprises' : '/reprise');
        $user = $request->getUser();
        if (null !== $user) {
            $this->userNotifications->notifyInternal(
                $user,
                'trade-in:'.$request->getReference().':'.$scenario.':'.$request->getStatus()->value,
                'Reprise mise à jour',
                'Votre demande de reprise '.$request->getReference().' est à l’état : '.$this->statusLabel($request->getStatus()).'.',
                '/reprises',
                $scenario,
            );

            if (!$this->userNotifications->shouldSendEmail($user)) {
                return;
            }
        }

        $context = [
            'customer_name' => $name,
            'trade_in_reference' => $request->getReference(),
            'trade_in_product' => $this->productLabel($request),
            'trade_in_estimate' => $this->estimateLabel($request),
            'trade_in_status' => $this->statusLabel($request->getStatus()),
            'trade_in_tracking_url' => $trackingUrl,
        ] + $extraContext;
        $fallback = $this->fallback($scenario);
        $content = $this->templates->renderScenario($scenario, $context, $fallback);
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($request->getEmail(), $name))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

        try {
            $this->mailer->send($email);
        } catch (\RuntimeException $exception) {
            $this->logger->error('Trade-in notification email failed.', [
                'scenario' => $scenario,
                'reference' => $request->getReference(),
                'exception' => $exception,
            ]);
        }
    }

    /** @return array{subject: string, html: string, text: string} */
    private function fallback(string $scenario): array
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

    private function productLabel(TradeInRequest $request): string
    {
        return trim(implode(' ', array_filter([$request->getBrand(), $request->getProductName(), $request->getModel()])));
    }

    private function estimateLabel(TradeInRequest $request): string
    {
        return $this->formatCents($request->getEstimatedMinCents()).' à '.$this->formatCents($request->getEstimatedMaxCents());
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    private function statusLabel(TradeInStatus $status): string
    {
        return match ($status) {
            TradeInStatus::SUBMITTED => 'Demande reçue',
            TradeInStatus::UNDER_REVIEW => 'En cours d’étude',
            TradeInStatus::OFFER_SENT => 'Offre envoyée',
            TradeInStatus::ACCEPTED => 'Offre acceptée',
            TradeInStatus::DECLINED => 'Offre refusée',
            TradeInStatus::RECEIVED => 'Matériel reçu',
            TradeInStatus::INSPECTED => 'Matériel inspecté',
            TradeInStatus::COMPLETED => 'Reprise finalisée',
            TradeInStatus::CANCELLED => 'Demande annulée',
            TradeInStatus::EXPIRED => 'Offre expirée',
        };
    }
}
