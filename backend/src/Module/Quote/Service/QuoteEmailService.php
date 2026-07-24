<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QuoteEmailService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuoteCreatedEmailContentProvider $content,
        private QuoteEmailDeliveryService $delivery,
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

        $this->sendCreated($quote);
        $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return true;
    }

    /** @return array{to: string, attachmentIncluded: bool, transport: string} */
    public function sendCreated(Quote $quote, ?string $overrideRecipient = null): array
    {
        $recipient = trim((string) ($overrideRecipient ?? $quote->getCustomerEmail()));
        if ('' === $recipient || false === filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('' === $recipient ? 'Aucune adresse e-mail destinataire n’est renseignée pour ce devis.' : 'L’adresse e-mail du destinataire est invalide.');
        }

        return $this->delivery->deliver($quote, $recipient, $this->content->build($quote));
    }
}
