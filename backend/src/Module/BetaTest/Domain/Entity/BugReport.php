<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Entity;

use App\Module\BetaTest\Domain\Enum\BugReportStatus;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'beta_bug_reports')]
#[ORM\Index(name: 'idx_beta_bug_status', columns: ['status'])]
class BugReport
{
    public const STATUS_SUBMITTED = BugReportStatus::SUBMITTED->value;
    public const STATUS_UNDER_REVIEW = BugReportStatus::UNDER_REVIEW->value;
    public const STATUS_NEED_INFO = BugReportStatus::NEED_INFO->value;
    public const STATUS_PLANNED = BugReportStatus::PLANNED->value;
    public const STATUS_RESOLVED = BugReportStatus::RESOLVED->value;
    public const STATUS_DUPLICATE = BugReportStatus::DUPLICATE->value;
    public const STATUS_REJECTED = BugReportStatus::REJECTED->value;

    /** @var list<string> */
    /** @var list<string> */
    public const ALLOWED_STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_NEED_INFO,
        self::STATUS_PLANNED,
        self::STATUS_RESOLVED,
        self::STATUS_DUPLICATE,
        self::STATUS_REJECTED,
    ];

    /** @var list<string> */
    public const CLOSED_STATUSES = [
        self::STATUS_RESOLVED,
        self::STATUS_DUPLICATE,
        self::STATUS_REJECTED,
    ];

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: User::class)] #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private User $reporter;
    #[ORM\ManyToOne(targetEntity: BetaCampaign::class)] #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?BetaCampaign $campaign;
    #[ORM\ManyToOne(targetEntity: User::class)] #[ORM\JoinColumn(name: 'assigned_to_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')] private ?User $assignedTo = null;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $assignedAt = null;
    #[ORM\ManyToOne(targetEntity: self::class)] #[ORM\JoinColumn(name: 'duplicate_of_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')] private ?self $duplicateOf = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $duplicateReason = null;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $duplicatedAt = null;
    #[ORM\Column(length: 180)] private string $title;
    #[ORM\Column(type: 'text')] private string $description;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $expectedBehavior;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $actualBehavior;
    #[ORM\Column(length: 20)] private string $severity;
    #[ORM\Column(length: 30, enumType: BugReportStatus::class)] private BugReportStatus $status = BugReportStatus::SUBMITTED;
    #[ORM\Column(length: 500, nullable: true)] private ?string $pageUrl;
    /** @var list<string> */
    #[ORM\Column(type: 'json')] private array $attachments = [];
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $lastAdminReplyAt = null;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $lastReporterReplyAt = null;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $createdAt;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $updatedAt;
    /**
     * @param array{
     *   reporter?: User,
     *   campaign?: ?BetaCampaign,
     *   title?: string,
     *   description?: string,
     *   expectedBehavior?: ?string,
     *   actualBehavior?: ?string,
     *   severity?: string,
     *   pageUrl?: ?string,
     *   attachments?: list<string>
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'reporter' => null,
            'campaign' => null,
            'title' => '',
            'description' => '',
            'expectedBehavior' => null,
            'actualBehavior' => null,
            'severity' => '',
            'pageUrl' => null,
            'attachments' => [],
        ], $payload ?? []);
        if (!$data['reporter'] instanceof User) {
            throw new \InvalidArgumentException('Le rapport de bug doit être associé à un utilisateur.');
        }
        $this->reporter = $data['reporter'];
        $this->campaign = $data['campaign'] instanceof BetaCampaign ? $data['campaign'] : null;
        $this->title = (string) $data['title'];
        $this->description = (string) $data['description'];
        $this->expectedBehavior = $data['expectedBehavior'];
        $this->actualBehavior = $data['actualBehavior'];
        $this->severity = (string) $data['severity'];
        $this->pageUrl = $data['pageUrl'];
        $this->attachments = is_array($data['attachments']) ? array_values(array_filter($data['attachments'], 'is_string')) : [];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): User
    {
        return $this->reporter;
    }

    public function getCampaign(): ?BetaCampaign
    {
        return $this->campaign;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getExpectedBehavior(): ?string
    {
        return $this->expectedBehavior;
    }

    public function getActualBehavior(): ?string
    {
        return $this->actualBehavior;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function setStatus(BugReportStatus|string $status): self
    {
        if (!$status instanceof BugReportStatus) {
            $status = BugReportStatus::tryFrom($status);
            if (null === $status) {
                throw new \InvalidArgumentException('État de signalement invalide.');
            }
        }

        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function assignTo(?User $user): self
    {
        $this->assignedTo = $user;
        $this->assignedAt = null !== $user ? new \DateTimeImmutable() : null;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAssignedAt(): ?\DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function getDuplicateOf(): ?self
    {
        return $this->duplicateOf;
    }

    public function getDuplicateReason(): ?string
    {
        return $this->duplicateReason;
    }

    public function markDuplicateOf(?self $report, ?string $reason = null): self
    {
        $this->duplicateOf = $report;
        $this->duplicateReason = null !== $reason && '' !== trim($reason) ? trim($reason) : null;
        $this->duplicatedAt = null !== $report ? new \DateTimeImmutable() : null;
        if (null !== $report) {
            $this->status = BugReportStatus::DUPLICATE;
        }
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDuplicatedAt(): ?\DateTimeImmutable
    {
        return $this->duplicatedAt;
    }

    public function recordAdminReply(): self
    {
        $now = new \DateTimeImmutable();
        $this->lastAdminReplyAt = $now;
        $this->updatedAt = $now;

        return $this;
    }

    public function recordReporterReply(): self
    {
        $now = new \DateTimeImmutable();
        $this->lastReporterReplyAt = $now;
        $this->updatedAt = $now;

        return $this;
    }

    public function getLastAdminReplyAt(): ?\DateTimeImmutable
    {
        return $this->lastAdminReplyAt;
    }

    public function getLastReporterReplyAt(): ?\DateTimeImmutable
    {
        return $this->lastReporterReplyAt;
    }

    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    /**
     * @return list<string>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
