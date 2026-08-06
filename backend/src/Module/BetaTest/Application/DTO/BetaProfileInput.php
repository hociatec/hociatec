<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\DTO;

use App\Module\BetaTest\Application\Mapper\BetaProfileChoices;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BetaProfileInput
{
    /** @var list<string> */
    #[Assert\Count(min: 1)]
    public array $availability;
    #[Assert\NotBlank]
    #[Assert\Length(max: 5000)]
    public string $motivation;
    #[Assert\NotBlank]
    public string $testingExperience;
    #[Assert\NotBlank]
    public string $bugDescriptionAbility;
    #[Assert\NotBlank]
    public string $technicalKnowledge;
    public string $accessibilityNeed;
    /** @var list<string> */
    #[Assert\Count(min: 1)]
    public array $assistiveTools;
    /** @var list<string> */
    #[Assert\Count(min: 1)]
    public array $devices;
    /** @var list<string> */
    #[Assert\Count(min: 1)]
    public array $browsers;
    /** @var list<string> */
    #[Assert\Count(min: 1)]
    public array $testingTypes;
    #[Assert\IsTrue(message: 'Le consentement au programme bêta est obligatoire.')]
    public bool $consent;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->availability = $data['availability'];
        $this->motivation = (string) $data['motivation'];
        $this->testingExperience = (string) $data['testingExperience'];
        $this->bugDescriptionAbility = (string) $data['bugDescriptionAbility'];
        $this->technicalKnowledge = (string) $data['technicalKnowledge'];
        $this->accessibilityNeed = (string) $data['accessibilityNeed'];
        $this->assistiveTools = $data['assistiveTools'];
        $this->devices = $data['devices'];
        $this->browsers = $data['browsers'];
        $this->testingTypes = $data['testingTypes'];
        $this->consent = (bool) $data['consent'];
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

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['availability', 'motivation', 'testingExperience', 'bugDescriptionAbility', 'technicalKnowledge', 'accessibilityNeed', 'assistiveTools', 'devices', 'browsers', 'testingTypes', 'consent'];
        $defaults = array_fill_keys($keys, null);
        $defaults['availability'] = [];
        $defaults['motivation'] = '';
        $defaults['testingExperience'] = '';
        $defaults['bugDescriptionAbility'] = '';
        $defaults['technicalKnowledge'] = '';
        $defaults['accessibilityNeed'] = 'none';
        $defaults['assistiveTools'] = [];
        $defaults['devices'] = [];
        $defaults['browsers'] = [];
        $defaults['testingTypes'] = [];
        $defaults['consent'] = false;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
