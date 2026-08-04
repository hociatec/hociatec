<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\DTO;

use App\Module\BetaTest\Application\Service\BetaProfileChoices;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BetaProfileInput
{
    /**
     * @param list<string> $availability
     * @param list<string> $assistiveTools
     * @param list<string> $devices
     * @param list<string> $browsers
     * @param list<string> $testingTypes
     */
    public function __construct(
        #[Assert\Count(min: 1)]
        public array $availability,
        #[Assert\NotBlank, Assert\Length(max: 5000)] public string $motivation,
        #[Assert\NotBlank] public string $testingExperience,
        #[Assert\NotBlank] public string $bugDescriptionAbility,
        #[Assert\NotBlank] public string $technicalKnowledge,
        public string $accessibilityNeed,
        #[Assert\Count(min: 1)] public array $assistiveTools,
        #[Assert\Count(min: 1)] public array $devices,
        #[Assert\Count(min: 1)] public array $browsers,
        #[Assert\Count(min: 1)] public array $testingTypes,
        #[Assert\IsTrue(message: 'Le consentement au programme bêta est obligatoire.')] public bool $consent,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $list = static fn (string $key): array => BetaProfileChoices::normalizeList($payload[$key] ?? [], $key);
        $text = static fn (string $key): string => is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
        $serializedRequired = static fn (string $key): string => BetaProfileChoices::serializeList($list($key));
        $technicalKnowledge = BetaProfileChoices::serializeList($list('technicalKnowledge'));

        return new self($list('availability'), $text('motivation'), $serializedRequired('testingExperience'), $serializedRequired('bugDescriptionAbility'), $technicalKnowledge, 'none', $list('assistiveTools'), $list('devices'), $list('browsers'), $list('testingTypes'), true === ($payload['betaConsent'] ?? false));
    }
}
