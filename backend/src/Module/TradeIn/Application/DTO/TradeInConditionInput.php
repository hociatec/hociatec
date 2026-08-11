<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TradeInConditionInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['new', 'excellent', 'good', 'fair', 'poor'])]
        public string $conditionGrade,
        public bool $functional,
        public bool $hasAccessories,
        public bool $hasProofOfPurchase,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $description,
    ) {
    }
}
