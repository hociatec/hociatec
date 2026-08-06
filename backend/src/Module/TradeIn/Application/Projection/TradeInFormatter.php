<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Projection;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;

final class TradeInFormatter
{
    /** @return array<string,mixed> */
    public function format(TradeInRequest $request, bool $private = true): array
    {
        $data = [
            'id' => $request->getId(), 'reference' => $request->getReference(), 'status' => $request->getStatus()->value, 'statusLabel' => $this->statusLabel($request->getStatus()), 'allowedNextStatuses' => array_map(fn (TradeInStatus $status): string => $status->value, $this->nextStatuses($request->getStatus())), 'allowedNextStatusDetails' => array_map(fn (TradeInStatus $status): array => ['value' => $status->value, 'label' => $this->statusLabel($status)], $this->nextStatuses($request->getStatus())),
            'category' => $request->getCategory(), 'categoryLabel' => $this->categoryLabel($request->getCategory()), 'productName' => $request->getProductName(), 'purchasePriceCents' => $request->getPurchasePriceCents(), 'purchaseYear' => $request->getPurchaseYear(), 'brand' => $request->getBrand(), 'model' => $request->getModel(),
            'conditionGrade' => $request->getConditionGrade(), 'conditionLabel' => $this->conditionLabel($request->getConditionGrade()), 'functional' => $request->isFunctional(), 'hasAccessories' => $request->hasAccessories(), 'hasProofOfPurchase' => $request->hasProofOfPurchase(),
            'description' => $request->getDescription(), 'catalogProductId' => $request->getCatalogProductId(), 'catalogProductName' => $request->getCatalogProductName(), 'estimatedMinCents' => $request->getEstimatedMinCents(), 'estimatedMaxCents' => $request->getEstimatedMaxCents(),
            'offerCents' => $request->getOfferCents(), 'finalOfferCents' => $request->getFinalOfferCents(), 'paymentMethod' => $request->getPaymentMethod(), 'paymentStatus' => $request->getPaymentStatus(), 'transactionReference' => $request->getTransactionReference(), 'paidAt' => $request->getPaidAt()?->format(DATE_ATOM), 'ribAvailable' => null !== $request->getRibPath(), 'ribOriginalName' => $request->getRibOriginalName(), 'receiptAvailable' => null !== $request->getReceiptPath(), 'voucherCode' => $request->getVoucherCode(), 'closedAt' => $request->getClosedAt()?->format(DATE_ATOM), 'adminNote' => $request->getAdminNote(), 'offerExpiresAt' => $request->getOfferExpiresAt()?->format(DATE_ATOM), 'createdAt' => $request->getCreatedAt()->format(DATE_ATOM),
        ];
        if ($private) {
            $data['contact'] = ['firstName' => $request->getFirstName(), 'lastName' => $request->getLastName(), 'email' => $request->getEmail(), 'phone' => $request->getPhone()];
        }

        return $data;
    }

    public function statusLabel(TradeInStatus $status): string
    {
        return $status->label();
    }

    public function conditionLabel(string $condition): string
    {
        return [
            'comme_neuf' => 'Comme neuf',
            'tres_bon' => 'Très bon état',
            'bon' => 'Bon état',
            'correct' => 'État correct',
            'hors_service' => 'Hors service / pour pièces',
        ][$condition] ?? $condition;
    }

    public function categoryLabel(string $category): string
    {
        return [
            'smartphone' => 'Smartphone', 'ordinateur' => 'Ordinateur', 'tablette' => 'Tablette', 'console' => 'Console', 'appareil-photo' => 'Appareil photo', 'audio' => 'Audio', 'electromenager' => 'Électroménager', 'autre' => 'Autre',
        ][$category] ?? $category;
    }

    /** @return list<TradeInStatus> */
    private function nextStatuses(TradeInStatus $status): array
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
