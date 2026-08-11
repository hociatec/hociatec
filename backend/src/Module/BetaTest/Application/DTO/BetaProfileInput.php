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
    #[Assert\Length(max: 255)]
    public string $testingExperience;
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $bugDescriptionAbility;
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $technicalKnowledge;
    #[Assert\Length(max: 255)]
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

    /**
     * @param array{
     *   availability?: list<string>,
     *   motivation?: string,
     *   testingExperience?: string,
     *   bugDescriptionAbility?: string,
     *   technicalKnowledge?: string,
     *   accessibilityNeed?: string,
     *   assistiveTools?: list<string>,
     *   devices?: list<string>,
     *   browsers?: list<string>,
     *   testingTypes?: list<string>,
     *   consent?: bool
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'availability' => [],
            'motivation' => '',
            'testingExperience' => '',
            'bugDescriptionAbility' => '',
            'technicalKnowledge' => '',
            'accessibilityNeed' => 'none',
            'assistiveTools' => [],
            'devices' => [],
            'browsers' => [],
            'testingTypes' => [],
            'consent' => false,
        ], $payload ?? []);
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

        return new self([
            'availability' => $list('availability'),
            'motivation' => $text('motivation'),
            'testingExperience' => $serializedRequired('testingExperience'),
            'bugDescriptionAbility' => $serializedRequired('bugDescriptionAbility'),
            'technicalKnowledge' => $technicalKnowledge,
            'accessibilityNeed' => 'none',
            'assistiveTools' => $list('assistiveTools'),
            'devices' => $list('devices'),
            'browsers' => $list('browsers'),
            'testingTypes' => $list('testingTypes'),
            'consent' => true === ($payload['betaConsent'] ?? false),
        ]);
    }
}
