<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Order\Service\OrderFormatter;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;
use App\Module\Quote\Entity\Service as QuoteServiceEntity;

final class QuoteFormatter
{
    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatService(QuoteServiceEntity $service): array
    {
        $durationValue = $service->getDurationValue();
        $durationUnit = $service->getDurationUnit();

        return [
            'id' => $service->getId(),
            'title' => $service->getTitle(),
            'description' => $service->getDescription(),
            'unit' => $service->getUnit(),
            'isFeaturedHome' => $service->isFeaturedHome(),
            'imageUrl' => self::formatServiceImageUrl($service),
            'imageAlt' => $service->getImageAlt(),
            'durationValue' => $durationValue,
            'durationUnit' => $durationUnit,
            'durationLabel' => self::formatServiceDurationLabel($durationValue, $durationUnit),
            'priceCents' => $service->getPriceCents(),
            'vatRate' => $service->getVatRateBps() / 100,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatQuote(Quote $quote, QuoteCalculator $calculator): array
    {
        $totals = $calculator->computeTotals($quote);

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
                static fn (QuoteItem $item) => self::formatItem($item),
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
            'convertedOrder' => null !== $quote->getConvertedOrder()
                ? OrderFormatter::formatOrder($quote->getConvertedOrder())
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatItem(QuoteItem $item): array
    {
        $qty = $item->getQuantity();
        $calc = new QuoteCalculator();
        $line = $calc->computeItemTotals($item);

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

    private static function formatServiceImageUrl(QuoteServiceEntity $service): ?string
    {
        if (null !== $service->getImageName() && '' !== trim($service->getImageName())) {
            return sprintf('/uploads/services/%s', ltrim($service->getImageName(), '/'));
        }

        if (null !== $service->getImageExternalUrl() && '' !== trim($service->getImageExternalUrl())) {
            return trim($service->getImageExternalUrl());
        }

        return null;
    }

    private static function formatServiceDurationLabel(?int $durationValue, ?string $durationUnit): ?string
    {
        if (null === $durationValue || $durationValue <= 0 || null === $durationUnit || '' === $durationUnit) {
            return null;
        }

        if ('day' === $durationUnit) {
            return $durationValue.' '.($durationValue > 1 ? 'jours' : 'jour');
        }

        return $durationValue.' '.($durationValue > 1 ? 'heures' : 'heure');
    }
}
