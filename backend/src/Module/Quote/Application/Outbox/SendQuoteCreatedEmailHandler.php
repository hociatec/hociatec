<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Outbox;

use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Quote\Application\Port\QuotePersistencePort;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Provider\QuoteCreatedEmailContentProvider;
use App\Module\Quote\Application\Workflow\QuoteEmailDeliveryService;
use App\Module\Quote\Domain\Entity\Quote;

final readonly class SendQuoteCreatedEmailHandler implements OutboxEventHandler
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private QuotePersistencePort $persistence,
        private QuoteCreatedEmailContentProvider $content,
        private QuoteEmailDeliveryService $delivery,
    ) {
    }

    public function supports(OutboxEvent $event): bool
    {
        return 'quote.created_email_requested' === $event->getType();
    }

    public function handle(OutboxEvent $event): void
    {
        $payload = $event->getPayload();
        $quoteId = $payload['quoteId'] ?? null;
        $recipient = $payload['recipient'] ?? null;
        $force = $payload['force'] ?? false;
        if (!is_int($quoteId) || !is_string($recipient) || '' === trim($recipient) || !is_bool($force)) {
            throw new \RuntimeException('Quote email outbox payload is invalid.');
        }

        $quote = $this->quotes->find($quoteId);
        if (!$quote instanceof Quote) {
            return;
        }

        if (!$force && null !== $quote->getCreatedEmailSentAt()) {
            return;
        }

        $this->delivery->deliver($quote, $recipient, $this->content->build($quote), $event->getKey());

        if ($force) {
            return;
        }

        $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        $this->persistence->flush();
    }
}
