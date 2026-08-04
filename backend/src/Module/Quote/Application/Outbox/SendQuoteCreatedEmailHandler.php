<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Outbox;

use App\Module\Outbox\Application\OutboxEventHandler;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Quote\Application\Service\QuoteCreatedEmailContentProvider;
use App\Module\Quote\Application\Service\QuoteEmailDeliveryService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;

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
