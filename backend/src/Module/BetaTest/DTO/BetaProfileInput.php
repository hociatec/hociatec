<?php

declare(strict_types=1);

namespace App\Module\BetaTest\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BetaProfileInput
{
    public const ACCESSIBILITY_NEEDS = ['blind', 'low_vision', 'none'];
    public const TOOLS = ['nvda', 'jaws', 'voiceover', 'talkback', 'narrator', 'magnifier', 'keyboard', 'braille', 'other'];
    public const DEVICES = ['windows', 'macos', 'linux', 'android', 'ios'];
    public const BROWSERS = ['chrome', 'firefox', 'edge', 'safari', 'other'];
    public const TEST_TYPES = ['bugs', 'accessibility', 'usability', 'mobile', 'performance', 'features'];

    public function __construct(
        #[Assert\Count(min: 1), Assert\All([new Assert\Choice(choices: ['weekdays', 'evenings', 'weekends', 'flexible'])])]
        public array $availability,
        #[Assert\NotBlank, Assert\Length(max: 5000)] public string $motivation,
        #[Assert\NotBlank, Assert\Length(max: 5000)] public string $testingExperience,
        #[Assert\NotBlank, Assert\Length(max: 5000)] public string $bugDescriptionAbility,
        #[Assert\Length(max: 5000)] public ?string $technicalKnowledge,
        #[Assert\Choice(choices: self::ACCESSIBILITY_NEEDS)] public string $accessibilityNeed,
        #[Assert\All([new Assert\Choice(choices: self::TOOLS)])] public array $assistiveTools,
        #[Assert\Count(min: 1), Assert\All([new Assert\Choice(choices: self::DEVICES)])] public array $devices,
        #[Assert\Count(min: 1), Assert\All([new Assert\Choice(choices: self::BROWSERS)])] public array $browsers,
        #[Assert\Count(min: 1), Assert\All([new Assert\Choice(choices: self::TEST_TYPES)])] public array $testingTypes,
        #[Assert\IsTrue(message: 'Le consentement au programme bêta est obligatoire.')] public bool $consent,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $list = static fn (string $key): array => array_values(array_filter($payload[$key] ?? [], 'is_string'));
        $text = static fn (string $key): string => is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';

        return new self($list('availability'), $text('motivation'), $text('testingExperience'), $text('bugDescriptionAbility'), '' === $text('technicalKnowledge') ? null : $text('technicalKnowledge'), $text('accessibilityNeed'), $list('assistiveTools'), $list('devices'), $list('browsers'), $list('testingTypes'), true === ($payload['betaConsent'] ?? false));
    }
}
