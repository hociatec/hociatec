<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Workflow;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Port\QuotePdfRenderer;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Security\QuoteAccessPolicy;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerQuotePortalService
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private QuoteFormatter $formatter,
        private QuoteAccessPolicy $accessPolicy,
        private QuoteWorkflowService $workflow,
        private ?QuoteCalculator $calculator = null,
        private ?QuotePdfRenderer $pdf = null,
    ) {
    }

    /**
     * @return array{items:list<array<string,mixed>>, total:int}
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $items = $this->quotes->findByCustomerEmail($user->getEmail(), $limit, $offset);

        return [
            'items' => array_map(fn (Quote $quote): array => $this->formatter->formatQuote($quote), $items),
            'total' => $this->quotes->countByCustomerEmail($user->getEmail()),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function showForUser(User $user, int $quoteId): ?array
    {
        $quote = $this->findViewableQuote($user, $quoteId);
        if (!$quote instanceof Quote) {
            return null;
        }

        return $this->formatter->formatQuote($quote);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function acceptForUser(User $user, int $quoteId): ?array
    {
        $quote = $this->findViewableQuote($user, $quoteId);
        if (!$quote instanceof Quote) {
            return null;
        }

        $this->assertConvertibleState($quote, 'Ce devis ne peut pas être accepté.', [Quote::STATUS_SENT, Quote::STATUS_ACCEPTED]);
        $quote->accept();
        $this->workflow->setStatus($quote, Quote::STATUS_ACCEPTED);

        return $this->formatter->formatQuote($quote);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function refuseForUser(User $user, int $quoteId): ?array
    {
        $quote = $this->findViewableQuote($user, $quoteId);
        if (!$quote instanceof Quote) {
            return null;
        }

        $this->assertConvertibleState($quote, 'Ce devis ne peut pas être refusé.', [Quote::STATUS_SENT, Quote::STATUS_REFUSED]);
        $quote->refuse();
        $this->workflow->setStatus($quote, Quote::STATUS_REFUSED);

        return $this->formatter->formatQuote($quote);
    }

    public function deleteForUser(User $user, int $quoteId): bool
    {
        $quote = $this->findViewableQuote($user, $quoteId);
        if (!$quote instanceof Quote) {
            return false;
        }

        $this->workflow->delete($quote);

        return true;
    }

    /**
     * @return array{content:string,filename:string}|null
     */
    public function renderPdfForUser(User $user, int $quoteId): ?array
    {
        if (!$this->calculator instanceof QuoteCalculator || !$this->pdf instanceof QuotePdfRenderer) {
            throw new \LogicException('Quote PDF dependencies are not configured.');
        }

        $quote = $this->findViewableQuote($user, $quoteId);
        if (!$quote instanceof Quote) {
            return null;
        }

        return [
            'content' => $this->pdf->render($quote, $this->calculator->computeTotals($quote)),
            'filename' => sprintf('%s.pdf', $quote->getNumber()),
        ];
    }

    private function findViewableQuote(User $user, int $quoteId): ?Quote
    {
        $quote = $this->quotes->find($quoteId);

        return $quote instanceof Quote && $this->accessPolicy->canView($user, $quote) ? $quote : null;
    }

    /**
     * @param list<string> $allowedStatuses
     */
    private function assertConvertibleState(Quote $quote, string $invalidStatusMessage, array $allowedStatuses): void
    {
        if (null !== $quote->getConvertedOrderId()) {
            throw new \DomainException('Ce devis est déjà converti en commande.');
        }

        if (!in_array($quote->getStatus(), $allowedStatuses, true)) {
            throw new \InvalidArgumentException($invalidStatusMessage);
        }
    }
}
