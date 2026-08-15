<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Projection;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Service\Application\Projection\ServiceFormatter;
use App\Module\Service\Domain\Entity\ServiceOffering;

final class QuoteFormatter
{
    public function __construct(
        private readonly QuoteCalculator $calculator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function formatService(ServiceOffering $service): array
    {
        return (new ServiceFormatter())->format($service);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatQuote(Quote $quote): array
    {
        $totals = $this->calculator->computeTotals($quote);

        $statusCode = $quote->getStatus();
        $statusLabel = QuoteStatusTranslator::toLabel($statusCode);

        return [
            'id' => $quote->getId(),
            'number' => $quote->getNumber(),
            'status' => $statusLabel,
            'statusCode' => $statusCode,
            'statusLabel' => $statusLabel,
            'customer' => [
                'name' => $quote->getCustomerName(),
                'email' => $quote->getCustomerEmail(),
                'company' => $quote->getCustomerCompany(),
                'address' => $quote->getCustomerAddress(),
            ],
            'items' => array_map(
                fn (QuoteItem $item) => $this->formatItem($item),
                [...$quote->getItems()]
            ),
            'discountCents' => $quote->getGlobalDiscountCents(),
            'shippingCents' => $quote->getShippingCents(),
            'conditions' => $quote->getConditions(),
            'validFrom' => $quote->getValidFrom()?->format('Y-m-d'),
            'validUntil' => $quote->getValidUntil()?->format('Y-m-d'),
            'totals' => [
                'ht' => $totals['totalHt'],
                'vat' => $totals['totalVat'],
                'ttc' => $totals['totalTtc'],
            ],
            'createdAt' => $quote->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $quote->getUpdatedAt()->format(DATE_ATOM),
            'sentAt' => $quote->getCreatedEmailSentAt()?->format(DATE_ATOM),
            'convertedOrder' => null !== $quote->getConvertedOrderId()
                ? ['id' => $quote->getConvertedOrderId(), 'number' => $quote->getConvertedOrderNumber()]
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatItem(QuoteItem $item): array
    {
        $qty = $item->getQuantity();
        $line = $this->calculator->computeItemTotals($item);

        return [
            'id' => $item->getId(),
            'type' => $item->getItemType(),
            'productId' => $item->getProductId(),
            'serviceId' => $item->getServiceId(),
            'name' => $item->getName(),
            'description' => $item->getDescription(),
            'unit' => $item->getUnit(),
            'quantity' => $qty,
            'unitPriceCents' => $item->getUnitPriceCents(),
            'vatRate' => $item->getVatRateBps() / 100,
            'discountCents' => $item->getDiscountCents(),
            'lineTotals' => [
                'ht' => $line['ht'],
                'vat' => $line['vat'],
                'ttc' => $line['ttc'],
            ],
        ];
    }
}
