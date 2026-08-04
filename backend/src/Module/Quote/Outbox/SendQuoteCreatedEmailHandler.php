<?php

declare(strict_types=1);

namespace App\Module\Quote\Outbox;

use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCreatedEmailContentProvider;
use App\Module\Quote\Service\QuoteEmailDeliveryService;
use App\Shared\Outbox\Entity\OutboxEvent;
use App\Shared\Outbox\OutboxEventHandler;

final readonly class SendQuoteCreatedEmailHandler implements OutboxEventHandler
{
    public function __construct(
        private QuoteRepository $quotes,
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
        if (!is_int($quoteId) || !is_string($recipient) || '' === trim($recipient)) {
            throw new \RuntimeException('Quote email outbox payload is invalid.');
        }

        $quote = $this->quotes->find($quoteId);
        if (!$quote instanceof Quote) {
            return;
        }

        $this->delivery->deliver($quote, $recipient, $this->content->build($quote));
    }
}
