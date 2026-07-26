<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderEmailScenarioInput
{
    public function __construct(
        #[Assert\Choice(choices: ['order_created', 'invoice_issued', 'current_status'])]
        public string $scenario,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['scenario'] ?? null) ? trim($payload['scenario']) : '');
    }
}
