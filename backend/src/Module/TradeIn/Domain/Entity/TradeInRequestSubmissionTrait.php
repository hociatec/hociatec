<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
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
}
