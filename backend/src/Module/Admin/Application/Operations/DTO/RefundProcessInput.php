<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefundProcessInput
{
    public function __construct(#[Assert\EqualTo('REMBOURSER')] public string $confirmation, #[Assert\Length(max: 255)] public ?string $paymentIntentId = null)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_string($p['confirmation'] ?? null) ? trim($p['confirmation']) : '', is_string($p['paymentIntentId'] ?? null) ? trim($p['paymentIntentId']) : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_filter(['confirmation' => $this->confirmation, 'paymentIntentId' => $this->paymentIntentId], static fn (mixed $v): bool => null !== $v);
    }
}
