<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\ValueObject;

final readonly class TradeInApplicant
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
    ) {
    }

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
