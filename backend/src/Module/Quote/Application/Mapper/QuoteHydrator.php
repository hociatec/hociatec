<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Mapper;

use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Factory\QuoteItemFactory;
use App\Module\Quote\Application\Workflow\QuoteService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Shared\Application\UnitOfWork;

final readonly class QuoteHydrator
{
    public function __construct(
        private UnitOfWork $persistence,
        private QuoteItemFactory $items,
        private ?\DateTimeImmutable $today = null,
    ) {
    }

    public function hydrate(Quote $quote, QuotePayload $payload, bool $clearItems = false): void
    {
        $quote->setCustomerName(QuoteValueNormalizer::strOrNull($payload->customer['name'] ?? null));
        $quote->setCustomerEmail(QuoteValueNormalizer::strOrNull($payload->customer['email'] ?? null));
        $quote->setCustomerCompany(QuoteValueNormalizer::strOrNull($payload->customer['company'] ?? null));
        $quote->setCustomerAddress(QuoteValueNormalizer::strOrNull($payload->customer['address'] ?? null));

        $status = QuoteStatusTranslator::toCode($payload->status);
        $quote->setStatus('' !== $status ? $status : Quote::STATUS_DRAFT);
        $quote->applyCommercialTerms($payload->discount->cents(), $payload->shipping->cents());

        if (null !== $payload->conditions || !$clearItems) {
            $quote->setConditions(QuoteValueNormalizer::strOrNull($payload->conditions) ?? QuoteService::DEFAULT_CONDITIONS);
        }

        $this->hydrateValidity($quote, $payload, $clearItems);
        $this->replaceItems($quote, $payload, $clearItems);
    }

    private function hydrateValidity(Quote $quote, QuotePayload $payload, bool $clearItems): void
    {
        if (null !== $payload->validFrom) {
            $quote->setValidFrom(QuoteValueNormalizer::dateOrNull($payload->validFrom));
        } elseif (!$clearItems && null === $quote->getValidFrom()) {
            $quote->setValidFrom($this->today());
        }

        if (null !== $payload->validUntil) {
            $quote->setValidUntil(QuoteValueNormalizer::dateOrNull($payload->validUntil));
        } elseif (!$clearItems && null === $quote->getValidUntil()) {
            $baseDate = $quote->getValidFrom() ?? $this->today();
            $quote->setValidUntil($baseDate->modify('+30 days'));
        }
    }

    private function replaceItems(Quote $quote, QuotePayload $payload, bool $clearItems): void
    {
        if ($clearItems) {
            foreach ($quote->getItems() as $existing) {
                $quote->removeItem($existing);
                $this->persistence->remove($existing);
            }
        }

        foreach ($payload->items as $raw) {
            $item = $this->items->fromPayload($raw);
            $quote->addItem($item);
            $this->persistence->persist($item);
        }
    }

    private function today(): \DateTimeImmutable
    {
        return ($this->today ?? new \DateTimeImmutable('today'))->setTime(0, 0);
    }
}
