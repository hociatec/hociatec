<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Shared\Outbox\Outbox;

final readonly class QuoteEmailService
{
    public function __construct(
        private QuotePersistence $persistence,
        private Outbox $outbox,
        private UserRepository $users,
        private UserCommunicationNotifier $userNotifications,
    ) {
    }

    /** @return array{to: string, attachmentIncluded: bool, transport: string} */
    public function send(Quote $quote, ?string $overrideRecipient = null): array
    {
        return $this->sendCreated($quote, $overrideRecipient);
    }

    public function sendCreatedIfNeeded(Quote $quote): bool
    {
        if (null !== $quote->getCreatedEmailSentAt()) {
            return false;
        }

        $result = $this->sendCreated($quote);
        if ('notification_only' === $result['transport']) {
            return false;
        }

        $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        $this->persistence->flush();

        return true;
    }

    /** @return array{to: string, attachmentIncluded: bool, transport: string} */
    public function sendCreated(Quote $quote, ?string $overrideRecipient = null): array
    {
        $recipient = trim((string) ($overrideRecipient ?? $quote->getCustomerEmail()));
        if ('' === $recipient || false === filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('' === $recipient ? 'Aucune adresse e-mail destinataire n’est renseignée pour ce devis.' : 'L’adresse e-mail du destinataire est invalide.');
        }

        $user = $this->users->findOneByEmailInsensitive($recipient);
        if ($user instanceof User) {
            $quoteId = $quote->getId();
            if (null !== $quoteId) {
                $this->userNotifications->notifyInternal(
                    $user,
                    'quote:'.$quoteId.':created',
                    'Devis disponible',
                    'Votre devis '.$quote->getNumber().' est disponible.',
                    '/quotes/me/'.$quoteId,
                    'quote_created',
                );
            }

            if (!$this->userNotifications->shouldSendEmail($user)) {
                return ['to' => $recipient, 'attachmentIncluded' => false, 'transport' => 'notification_only'];
            }
        }

        $quoteId = $quote->getId();
        if (null === $quoteId) {
            throw new \InvalidArgumentException('Devis invalide.');
        }

        $this->outbox->record('quote.email.created.'.$quoteId.'.'.hash('sha256', $recipient).'.'.bin2hex(random_bytes(8)), 'quote.created_email_requested', [
            'quoteId' => $quoteId,
            'recipient' => $recipient,
        ]);

        return ['to' => $recipient, 'attachmentIncluded' => true, 'transport' => 'outbox'];
    }
}
