<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MarketingTemplateInput
{
    public function __construct(
        #[Assert\NotBlank] #[Assert\Length(max: 150)] public string $name,
        #[Assert\NotBlank] #[Assert\Length(max: 150)] public string $slug,
        #[Assert\NotBlank] #[Assert\Length(max: 100)] public string $scenarioKey,
        #[Assert\NotBlank] #[Assert\Length(max: 255)] public string $subjectTemplate,
        #[Assert\NotBlank] public string $htmlBody,
        public ?string $textBody = null,
        public bool $isActive = true,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::string($payload, 'name'), self::string($payload, 'slug'), self::string($payload, 'scenarioKey'),
            self::string($payload, 'subjectTemplate'), self::string($payload, 'htmlBody'),
            isset($payload['textBody']) ? self::string($payload, 'textBody') : null,
            is_bool($payload['isActive'] ?? null) ? $payload['isActive'] : true,
        );
    }

    /** @param array<string,mixed> $payload */
    private static function string(array $payload, string $key): string
    {
        return is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
    }
}
