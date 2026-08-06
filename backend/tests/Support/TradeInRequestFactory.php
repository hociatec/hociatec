<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInCatalogReference;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\TradeIn\Domain\ValueObject\TradeInTechnicalIdentity;
use App\Module\User\Domain\Entity\User;

final class TradeInRequestFactory
{
    public static function submitted(
        string $reference,
        ?User $user,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $category,
        string $productName,
        int $purchasePriceCents,
        int $purchaseYear,
        ?string $brand,
        ?string $model,
        ?string $serialNumber,
        string $conditionGrade,
        bool $functional,
        bool $hasAccessories,
        bool $hasProofOfPurchase,
        string $description,
        ?int $catalogProductId,
        ?string $catalogProductName,
        int $estimatedMinCents,
        int $estimatedMaxCents,
        \DateTimeImmutable $consentAt,
    ): TradeInRequest {
        return TradeInRequest::fromSubmittedData(
            $reference,
            $user,
            new TradeInApplicant($firstName, $lastName, $email, $phone),
            new TradeInProductSnapshot(
                new TradeInProductIdentity($category, $productName, new TradeInTechnicalIdentity($brand, $model, $serialNumber), new TradeInCatalogReference($catalogProductId, $catalogProductName)),
                new TradeInPurchase($purchasePriceCents, $purchaseYear),
                new TradeInProductCondition($conditionGrade, $functional, $hasAccessories, $hasProofOfPurchase, $description),
            ),
            new TradeInEstimate($estimatedMinCents, $estimatedMaxCents, null, null),
            $consentAt,
        );
    }
}
