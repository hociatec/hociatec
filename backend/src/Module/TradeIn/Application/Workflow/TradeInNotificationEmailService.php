<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Notification\Application\Notification\TemplatedEmailFactory;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Shared\Application\Mail\EmailSender;
use Psr\Log\LoggerInterface;

final readonly class TradeInNotificationEmailService
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private EmailSender $mailer,
        private LoggerInterface $logger,
        private UserCommunicationNotifier $userNotifications,
        private string $mailerFrom,
        private TradeInNotificationMessageBuilder $messages,
    ) {
    }

    public function sendCreated(TradeInRequest $request): void
    {
        $this->send($request, 'trade_in_created', [
            'trade_in_estimate' => $this->messages->estimateLabel($request),
        ]);
    }

    public function sendStatusChanged(TradeInRequest $request): void
    {
        $offer = null !== $request->getOfferCents() ? $this->messages->formatCents($request->getOfferCents()) : '';
        $this->send($request, 'trade_in_status_changed', [
            'trade_in_offer_block' => '' !== $offer ? 'Offre définitive proposée : '.$offer.'.' : '',
            'trade_in_offer_text' => '' !== $offer ? 'Offre définitive proposée : '.$offer.'.' : '',
        ]);
    }

    /** @param array<string, string> $extraContext */
    private function send(TradeInRequest $request, string $scenario, array $extraContext): void
    {
        $name = trim($request->getFirstName().' '.$request->getLastName());
        $user = $request->getUser();
        if (null !== $user) {
            $this->userNotifications->notifyInternal(
                $user,
                'trade-in:'.$request->getReference().':'.$scenario.':'.$request->getStatus()->value,
                'Reprise mise à jour',
                $this->messages->internalMessage($request),
                '/reprises',
                $scenario,
            );

            if (!$this->userNotifications->shouldSendEmail($user)) {
                return;
            }
        }

        $context = $this->messages->context($request, $extraContext);
        $fallback = $this->messages->fallback($scenario);
        $content = $this->templates->renderScenario($scenario, $context, $fallback);
        $email = TemplatedEmailFactory::create(
            $this->mailerFrom,
            'Hociatec',
            $request->getEmail(),
            $name,
            $content['subject'],
            $content['html'],
            $content['text'],
        );
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
}
