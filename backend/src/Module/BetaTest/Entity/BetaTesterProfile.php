<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Entity;

use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BetaTesterProfileRepository::class)]
#[ORM\Table(name: 'beta_tester_profiles')]
#[ORM\UniqueConstraint(name: 'uniq_beta_profile_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_beta_profile_status', columns: ['status'])]
class BetaTesterProfile
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_PENDING;

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
     * @param list<string> $availability
     * @param list<string> $assistiveTools
     * @param list<string> $devices
     * @param list<string> $browsers
     * @param list<string> $testingTypes
     */
    public function __construct(User $user, array $availability, string $motivation, string $testingExperience, string $bugDescriptionAbility, ?string $technicalKnowledge, string $accessibilityNeed, array $assistiveTools, array $devices, array $browsers, array $testingTypes, \DateTimeImmutable $consentAt, string $privacyNoticeVersion)
    {
        $this->user = $user;
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
        $this->consentAt = $consentAt;
        $this->privacyNoticeVersion = $privacyNoticeVersion;
        $this->createdAt = $consentAt;
        $this->updatedAt = $consentAt;
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
        return $this->status;
    }

    public function setStatus(string $status): self
    {
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

    public function updateFromInput(\App\Module\BetaTest\DTO\BetaProfileInput $input): self
    {
        $this->availability = $input->availability;
        $this->motivation = $input->motivation;
        $this->testingExperience = $input->testingExperience;
        $this->bugDescriptionAbility = $input->bugDescriptionAbility;
        $this->technicalKnowledge = $input->technicalKnowledge;
        $this->accessibilityNeed = $input->accessibilityNeed;
        $this->assistiveTools = $input->assistiveTools;
        $this->devices = $input->devices;
        $this->browsers = $input->browsers;
        $this->testingTypes = $input->testingTypes;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
