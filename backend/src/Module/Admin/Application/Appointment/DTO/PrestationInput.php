<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Appointment\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PrestationInput
{
    public function __construct(#[Assert\NotBlank] public string $name, #[Assert\Positive] public int $durationMinutes, public string|int|float $price)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $price = $payload['price'] ?? 0;

        return new self(is_string($payload['name'] ?? null) ? trim($payload['name']) : '', is_numeric($payload['durationMinutes'] ?? null) ? (int) $payload['durationMinutes'] : 0, is_string($price) || is_int($price) || is_float($price) ? $price : 0);
    }
}
