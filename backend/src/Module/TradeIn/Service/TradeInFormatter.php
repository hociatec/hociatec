<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Service;

use App\Module\TradeIn\Entity\TradeInRequest;

final class TradeInFormatter
{
    /** @return array<string,mixed> */
    public static function format(TradeInRequest $request, bool $private = true): array
    {
        $data = [
            'id' => $request->getId(), 'reference' => $request->getReference(), 'status' => $request->getStatus()->value,
            'category' => $request->getCategory(), 'productName' => $request->getProductName(), 'brand' => $request->getBrand(), 'model' => $request->getModel(),
            'conditionGrade' => $request->getConditionGrade(), 'functional' => $request->isFunctional(), 'hasAccessories' => $request->hasAccessories(), 'hasProofOfPurchase' => $request->hasProofOfPurchase(),
            'description' => $request->getDescription(), 'estimatedMinCents' => $request->getEstimatedMinCents(), 'estimatedMaxCents' => $request->getEstimatedMaxCents(),
            'offerCents' => $request->getOfferCents(), 'adminNote' => $request->getAdminNote(), 'offerExpiresAt' => $request->getOfferExpiresAt()?->format(DATE_ATOM), 'createdAt' => $request->getCreatedAt()->format(DATE_ATOM),
        ];
        if ($private) { $data['contact'] = ['firstName' => $request->getFirstName(), 'lastName' => $request->getLastName(), 'email' => $request->getEmail(), 'phone' => $request->getPhone()]; }

        return $data;
    }
}
