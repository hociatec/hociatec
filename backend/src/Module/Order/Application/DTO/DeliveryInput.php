<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

use App\Shared\Domain\ValueObject\Url;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeliveryInput
{
    public function __construct(#[Assert\NotBlank] public string $status, #[Assert\Length(max: 150)] public ?string $carrier, #[Assert\Length(max: 150)] public ?string $trackingNumber, public ?Url $trackingUrl, public ?string $estimatedAt)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        $url = is_string($p['trackingUrl'] ?? null) && '' !== trim($p['trackingUrl']) ? Url::fromString($p['trackingUrl']) : null;

        return new self(is_string($p['status'] ?? null) ? trim($p['status']) : '', is_string($p['carrier'] ?? null) ? trim($p['carrier']) : null, is_string($p['trackingNumber'] ?? null) ? trim($p['trackingNumber']) : null, $url, is_string($p['estimatedAt'] ?? null) ? trim($p['estimatedAt']) : null);
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return ['status' => $this->status, 'carrier' => $this->carrier, 'trackingNumber' => $this->trackingNumber, 'trackingUrl' => $this->trackingUrl?->value(), 'estimatedAt' => $this->estimatedAt];
    }
}
