<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Entity;

use App\Module\BetaTest\Domain\Enum\BetaTesterStatus;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'beta_tester_profiles')]
#[ORM\UniqueConstraint(name: 'uniq_beta_profile_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_beta_profile_status', columns: ['status'])]
class BetaTesterProfile
{
    public const STATUS_PENDING = BetaTesterStatus::PENDING->value;
    public const STATUS_ACCEPTED = BetaTesterStatus::ACCEPTED->value;
    public const STATUS_PAUSED = BetaTesterStatus::PAUSED->value;
    public const STATUS_REJECTED = BetaTesterStatus::REJECTED->value;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 30, enumType: BetaTesterStatus::class)]
    private BetaTesterStatus $status = BetaTesterStatus::PENDING;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $availability = [];
    #[ORM\Column(type: 'text')]
    private string $motivation = '';
    #[ORM\Column(type: 'text')]
    private string $testingExperience = '';
    #[ORM\Column(type: 'text')]
    private string $bugDescriptionAbility = '';
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $technicalKnowledge = null;
    #[ORM\Column(length: 30)]
    private string $accessibilityNeed = 'none';
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $assistiveTools = [];
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $devices = [];
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $browsers = [];
    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $testingTypes = [];
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $consentAt;
    #[ORM\Column(length: 30)]
    private string $privacyNoticeVersion;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array{
     *   user?: User,
     *   availability?: list<string>,
     *   motivation?: string,
     *   testingExperience?: string,
     *   bugDescriptionAbility?: string,
     *   technicalKnowledge?: ?string,
     *   accessibilityNeed?: string,
     *   assistiveTools?: list<string>,
     *   devices?: list<string>,
     *   browsers?: list<string>,
     *   testingTypes?: list<string>,
     *   consentAt?: \DateTimeImmutable,
     *   privacyNoticeVersion?: string
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'user' => null,
            'availability' => [],
            'motivation' => '',
            'testingExperience' => '',
            'bugDescriptionAbility' => '',
            'technicalKnowledge' => null,
            'accessibilityNeed' => 'none',
            'assistiveTools' => [],
            'devices' => [],
            'browsers' => [],
            'testingTypes' => [],
            'consentAt' => new \DateTimeImmutable(),
            'privacyNoticeVersion' => 'unknown',
        ], $payload ?? []);
        if (!$data['user'] instanceof User) {
            throw new \InvalidArgumentException('Le profil bêta doit être associé à un utilisateur.');
        }
        $this->user = $data['user'];
        $this->availability = is_array($data['availability']) ? array_values(array_filter($data['availability'], 'is_string')) : [];
        $this->motivation = (string) $data['motivation'];
        $this->testingExperience = (string) $data['testingExperience'];
        $this->bugDescriptionAbility = (string) $data['bugDescriptionAbility'];
        $this->technicalKnowledge = null !== $data['technicalKnowledge'] ? (string) $data['technicalKnowledge'] : null;
        $this->accessibilityNeed = (string) $data['accessibilityNeed'];
        $this->assistiveTools = is_array($data['assistiveTools']) ? array_values(array_filter($data['assistiveTools'], 'is_string')) : [];
        $this->devices = is_array($data['devices']) ? array_values(array_filter($data['devices'], 'is_string')) : [];
        $this->browsers = is_array($data['browsers']) ? array_values(array_filter($data['browsers'], 'is_string')) : [];
        $this->testingTypes = is_array($data['testingTypes']) ? array_values(array_filter($data['testingTypes'], 'is_string')) : [];
        $this->consentAt = $data['consentAt'] instanceof \DateTimeImmutable ? $data['consentAt'] : new \DateTimeImmutable();
        $this->privacyNoticeVersion = (string) $data['privacyNoticeVersion'];
        $this->createdAt = $this->consentAt;
        $this->updatedAt = $this->consentAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function setStatus(BetaTesterStatus|string $status): self
    {
        if (!$status instanceof BetaTesterStatus) {
            $status = BetaTesterStatus::tryFrom($status);
            if (null === $status) {
                throw new \InvalidArgumentException('État de profil invalide.');
            }
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAvailability(): array
    {
        return $this->availability;
    }

    public function getMotivation(): string
    {
        return $this->motivation;
    }

    public function getTestingExperience(): string
    {
        return $this->testingExperience;
    }

    public function getBugDescriptionAbility(): string
    {
        return $this->bugDescriptionAbility;
    }

    public function getTechnicalKnowledge(): ?string
    {
        return $this->technicalKnowledge;
    }

    public function getAccessibilityNeed(): string
    {
        return $this->accessibilityNeed;
    }

    /**
     * @return list<string>
     */
    public function getAssistiveTools(): array
    {
        return $this->assistiveTools;
    }

    /**
     * @return list<string>
     */
    public function getDevices(): array
    {
        return $this->devices;
    }

    /**
     * @return list<string>
     */
    public function getBrowsers(): array
    {
        return $this->browsers;
    }

    /**
     * @return list<string>
     */
    public function getTestingTypes(): array
    {
        return $this->testingTypes;
    }

    public function getConsentAt(): \DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param list<string> $availability
     * @param list<string> $assistiveTools
     * @param list<string> $devices
     * @param list<string> $browsers
     * @param list<string> $testingTypes
     */
    public function update(
        array $availability,
        string $motivation,
        string $testingExperience,
        string $bugDescriptionAbility,
        ?string $technicalKnowledge,
        string $accessibilityNeed,
        array $assistiveTools,
        array $devices,
        array $browsers,
        array $testingTypes,
    ): self {
        $this->availability = $availability;
        $this->motivation = $motivation;
        $this->testingExperience = $testingExperience;
        $this->bugDescriptionAbility = $bugDescriptionAbility;
        $this->technicalKnowledge = $technicalKnowledge;
        $this->accessibilityNeed = $accessibilityNeed;
        $this->assistiveTools = $assistiveTools;
        $this->devices = $devices;
        $this->browsers = $browsers;
        $this->testingTypes = $testingTypes;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
