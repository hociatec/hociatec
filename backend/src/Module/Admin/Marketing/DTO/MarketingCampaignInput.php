<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MarketingCampaignInput
{
    /** @param array<string,mixed> $criteria */
    public function __construct(
        #[Assert\NotBlank] public string $name,
        #[Assert\NotBlank] public string $segmentKey,
        public array $criteria,
        #[Assert\NotBlank] #[Assert\Length(max: 255)] public string $subject,
        #[Assert\NotBlank] public string $htmlBody,
        public ?string $textBody = null,
        #[Assert\Positive] public ?int $templateId = null,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::string($payload, 'name'), self::string($payload, 'segmentKey'),
            is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [],
            self::string($payload, 'subject'), self::string($payload, 'htmlBody'),
            isset($payload['textBody']) ? self::string($payload, 'textBody') : null,
            is_numeric($payload['templateId'] ?? null) ? (int) $payload['templateId'] : null,
        );
    }

    /** @param array<string,mixed> $payload */
    private static function string(array $payload, string $key): string
    {
        return is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
    }
}
