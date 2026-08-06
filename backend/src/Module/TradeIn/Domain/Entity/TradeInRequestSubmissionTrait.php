<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\User\Domain\Entity\User;

trait TradeInRequestSubmissionTrait
{
    public static function fromSubmittedData(
        string $reference,
        ?User $user,
        TradeInApplicant $applicant,
        TradeInProductSnapshot $product,
        TradeInEstimate $estimate,
        \DateTimeImmutable $consentAt,
    ): self {
        return new self($reference, $user, $applicant, $product, $estimate, $consentAt);
    }

    /**
     * @deprecated prefer fromSubmittedData() with applicant, product and estimate value objects
     */
    public static function fromLegacySubmittedScalars(
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
    ): self {
        return self::fromSubmittedData(
            $reference,
            $user,
            new TradeInApplicant($firstName, $lastName, $email, $phone),
            new TradeInProductSnapshot(
                new TradeInProductIdentity($category, $productName, $brand, $model, $serialNumber, $catalogProductId, $catalogProductName),
                new TradeInPurchase($purchasePriceCents, $purchaseYear),
                new TradeInProductCondition($conditionGrade, $functional, $hasAccessories, $hasProofOfPurchase, $description),
            ),
            new TradeInEstimate($estimatedMinCents, $estimatedMaxCents, null, null),
            $consentAt,
        );
    }
}
