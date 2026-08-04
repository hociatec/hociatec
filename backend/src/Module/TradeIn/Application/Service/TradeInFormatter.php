<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Service;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;

final class TradeInFormatter
{
    /** @return array<string,mixed> */
    public static function format(TradeInRequest $request, bool $private = true): array
    {
        $data = [
            'id' => $request->getId(), 'reference' => $request->getReference(), 'status' => $request->getStatus()->value, 'statusLabel' => self::statusLabel($request->getStatus()), 'allowedNextStatuses' => array_map(static fn (TradeInStatus $status): string => $status->value, self::nextStatuses($request->getStatus())), 'allowedNextStatusDetails' => array_map(static fn (TradeInStatus $status): array => ['value' => $status->value, 'label' => self::statusLabel($status)], self::nextStatuses($request->getStatus())),
            'category' => $request->getCategory(), 'categoryLabel' => self::categoryLabel($request->getCategory()), 'productName' => $request->getProductName(), 'purchasePriceCents' => $request->getPurchasePriceCents(), 'purchaseYear' => $request->getPurchaseYear(), 'brand' => $request->getBrand(), 'model' => $request->getModel(),
            'conditionGrade' => $request->getConditionGrade(), 'conditionLabel' => self::conditionLabel($request->getConditionGrade()), 'functional' => $request->isFunctional(), 'hasAccessories' => $request->hasAccessories(), 'hasProofOfPurchase' => $request->hasProofOfPurchase(),
            'description' => $request->getDescription(), 'catalogProductId' => $request->getCatalogProductId(), 'catalogProductName' => $request->getCatalogProductName(), 'estimatedMinCents' => $request->getEstimatedMinCents(), 'estimatedMaxCents' => $request->getEstimatedMaxCents(),
            'offerCents' => $request->getOfferCents(), 'finalOfferCents' => $request->getFinalOfferCents(), 'paymentMethod' => $request->getPaymentMethod(), 'paymentStatus' => $request->getPaymentStatus(), 'transactionReference' => $request->getTransactionReference(), 'paidAt' => $request->getPaidAt()?->format(DATE_ATOM), 'ribAvailable' => null !== $request->getRibPath(), 'ribOriginalName' => $request->getRibOriginalName(), 'receiptAvailable' => null !== $request->getReceiptPath(), 'voucherCode' => $request->getVoucherCode(), 'closedAt' => $request->getClosedAt()?->format(DATE_ATOM), 'adminNote' => $request->getAdminNote(), 'offerExpiresAt' => $request->getOfferExpiresAt()?->format(DATE_ATOM), 'createdAt' => $request->getCreatedAt()->format(DATE_ATOM),
        ];
        if ($private) {
            $data['contact'] = ['firstName' => $request->getFirstName(), 'lastName' => $request->getLastName(), 'email' => $request->getEmail(), 'phone' => $request->getPhone()];
        }

        return $data;
    }

    public static function statusLabel(TradeInStatus $status): string
    {
        return match ($status) {
            TradeInStatus::SUBMITTED => 'Demande reçue',
            TradeInStatus::UNDER_REVIEW => 'En cours d’étude',
            TradeInStatus::OFFER_SENT => 'Offre envoyée',
            TradeInStatus::ACCEPTED => 'Offre acceptée',
            TradeInStatus::DECLINED => 'Offre refusée',
            TradeInStatus::RECEIVED => 'Matériel reçu',
            TradeInStatus::INSPECTED => 'Matériel inspecté',
            TradeInStatus::COMPLETED => 'Reprise terminée',
            TradeInStatus::CANCELLED => 'Demande annulée',
            TradeInStatus::EXPIRED => 'Offre expirée',
        };
    }

    public static function conditionLabel(string $condition): string
    {
        return [
            'comme_neuf' => 'Comme neuf',
            'tres_bon' => 'Très bon état',
            'bon' => 'Bon état',
            'correct' => 'État correct',
            'hors_service' => 'Hors service / pour pièces',
        ][$condition] ?? $condition;
    }

    public static function categoryLabel(string $category): string
    {
        return [
            'smartphone' => 'Smartphone', 'ordinateur' => 'Ordinateur', 'tablette' => 'Tablette', 'console' => 'Console', 'appareil-photo' => 'Appareil photo', 'audio' => 'Audio', 'electromenager' => 'Électroménager', 'autre' => 'Autre',
        ][$category] ?? $category;
    }

    /** @return list<TradeInStatus> */
    private static function nextStatuses(TradeInStatus $status): array
    {
        return match ($status) {
            TradeInStatus::SUBMITTED => [TradeInStatus::SUBMITTED, TradeInStatus::UNDER_REVIEW, TradeInStatus::OFFER_SENT, TradeInStatus::CANCELLED],
            TradeInStatus::UNDER_REVIEW => [TradeInStatus::UNDER_REVIEW, TradeInStatus::OFFER_SENT, TradeInStatus::CANCELLED],
            TradeInStatus::OFFER_SENT => [TradeInStatus::OFFER_SENT, TradeInStatus::ACCEPTED, TradeInStatus::DECLINED, TradeInStatus::EXPIRED, TradeInStatus::CANCELLED],
            TradeInStatus::ACCEPTED => [TradeInStatus::ACCEPTED, TradeInStatus::RECEIVED, TradeInStatus::CANCELLED],
            TradeInStatus::RECEIVED => [TradeInStatus::RECEIVED, TradeInStatus::INSPECTED, TradeInStatus::CANCELLED],
            TradeInStatus::INSPECTED => [TradeInStatus::INSPECTED, TradeInStatus::COMPLETED, TradeInStatus::CANCELLED],
            default => [$status],
        };
    }
}
