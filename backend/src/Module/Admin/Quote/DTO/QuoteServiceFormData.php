<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\DTO;

final readonly class QuoteServiceFormData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $billingMode,
        public ?int $durationValue,
        public ?string $durationUnit,
        public ?int $priceCents,
        public ?int $vatRateBps,
        public bool $updatesBillingMode,
        public bool $updatesDuration,
    ) {
    }
}
