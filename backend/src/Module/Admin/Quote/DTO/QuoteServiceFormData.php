<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        public bool $isFeaturedHome,
        public ?UploadedFile $imageFile,
        public ?string $imageUrl,
        public ?string $imageAlt,
        public bool $updatesBillingMode,
        public bool $updatesDuration,
    ) {
    }
}
