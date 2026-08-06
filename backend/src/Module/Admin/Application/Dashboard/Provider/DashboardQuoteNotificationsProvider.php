<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Domain\Entity\Quote;

final readonly class DashboardQuoteNotificationsProvider
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private QuoteFormatter $quoteFormatter,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function provide(): array
    {
        return [
            ...$this->acceptedQuotes(),
            ...$this->refusedQuotes(),
            ...$this->emailedQuotes(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function acceptedQuotes(): array
    {
        return array_map(fn (Quote $quote): array => [
            ...$this->quoteNotification($quote, 'quote_accepted', 'action', 'Devis accepté à convertir'),
            'quote' => $this->quoteFormatter->formatQuote($quote),
        ], $this->quotes->findAcceptedWaitingForConversion(8));
    }

    /** @return list<array<string, mixed>> */
    private function refusedQuotes(): array
    {
        return array_map(
            fn (Quote $quote): array => $this->quoteNotification($quote, 'quote_refused', 'info', 'Devis refusé'),
            $this->quotes->findRecentByStatuses([Quote::STATUS_REFUSED], 4),
        );
    }

    /** @return list<array<string, mixed>> */
    private function emailedQuotes(): array
    {
        return array_map(fn (Quote $quote): array => [
            ...$this->quoteNotification($quote, 'quote_email_sent', 'info', 'Devis envoyé au client'),
            'createdAt' => ($quote->getCreatedEmailSentAt() ?? $quote->getUpdatedAt())->format(DATE_ATOM),
        ], $this->quotes->findRecentlyEmailed(4));
    }

    /** @return array<string, mixed> */
    private function quoteNotification(Quote $quote, string $type, string $severity, string $title): array
    {
        $idPrefix = match ($type) {
            'quote_accepted' => 'quote-accepted',
            'quote_refused' => 'quote-refused',
            'quote_email_sent' => 'quote-emailed',
            default => str_replace('_', '-', $type),
        };

        return [
            'id' => $idPrefix.'-'.$quote->getId(),
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => sprintf('%s · %s', $quote->getNumber(), $quote->getCustomerEmail() ?? 'Client sans email'),
            'createdAt' => $quote->getUpdatedAt()->format(DATE_ATOM),
            'to' => '/admin/quotes/'.$quote->getId(),
            'resource' => [
                'type' => 'quote',
                'id' => $quote->getId(),
                'number' => $quote->getNumber(),
            ],
        ];
    }
}
