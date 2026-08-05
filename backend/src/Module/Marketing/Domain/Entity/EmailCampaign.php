<?php

declare(strict_types=1);

namespace App\Module\Marketing\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'marketing_email_campaigns')]
#[ORM\HasLifecycleCallbacks]
class EmailCampaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 140)]
    private string $name;

    #[ORM\Column(length: 60)]
    private string $segmentKey;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $criteria = [];

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?EmailTemplate $template = null;

    #[ORM\Column(length: 180)]
    private string $subjectSnapshot;

    #[ORM\Column(type: 'text')]
    private string $htmlSnapshot;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textSnapshot = null;

    #[ORM\Column(type: 'integer')]
    private int $recipientsCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $pendingCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $sentCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $failedCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $skippedCount = 0;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $createdByEmail = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $criteria
     */
    public function __construct(
        string $name,
        string $segmentKey,
        array $criteria,
        string $subjectSnapshot,
        string $htmlSnapshot,
        ?string $textSnapshot,
        int $recipientsCount,
        ?string $createdByEmail,
        ?EmailTemplate $template = null,
    ) {
        $this->name = $name;
        $this->segmentKey = $segmentKey;
        $this->criteria = $criteria;
        $this->subjectSnapshot = $subjectSnapshot;
        $this->htmlSnapshot = $htmlSnapshot;
        $this->textSnapshot = $textSnapshot;
        $this->recipientsCount = max(0, $recipientsCount);
        $this->createdByEmail = $createdByEmail;
        $this->template = $template;
        $now = new \DateTimeImmutable();
        $this->sentAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSegmentKey(): string
    {
        return $this->segmentKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function getSubjectSnapshot(): string
    {
        return $this->subjectSnapshot;
    }

    public function getHtmlSnapshot(): string
    {
        return $this->htmlSnapshot;
    }

    public function getTextSnapshot(): ?string
    {
        return $this->textSnapshot;
    }

    public function getRecipientsCount(): int
    {
        return $this->recipientsCount;
    }

    public function updateRecipientsCount(int $recipientsCount): void
    {
        $this->recipientsCount = max(0, $recipientsCount);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPendingCount(): int
    {
        return $this->pendingCount;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function registerRecipientStatus(string $status): void
    {
        $this->incrementStatus($status);
        if (EmailCampaignRecipient::STATUS_SKIPPED !== $status) {
            ++$this->recipientsCount;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function transitionRecipientStatus(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $this->decrementStatus($from);
        $this->incrementStatus($to);

        if (EmailCampaignRecipient::STATUS_SKIPPED === $from && EmailCampaignRecipient::STATUS_SKIPPED !== $to) {
            ++$this->recipientsCount;
        }
        if (EmailCampaignRecipient::STATUS_SKIPPED !== $from && EmailCampaignRecipient::STATUS_SKIPPED === $to) {
            $this->recipientsCount = max(0, $this->recipientsCount - 1);
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedByEmail(): ?string
    {
        return $this->createdByEmail;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function incrementStatus(string $status): void
    {
        match ($status) {
            EmailCampaignRecipient::STATUS_PENDING => ++$this->pendingCount,
            EmailCampaignRecipient::STATUS_SENT => ++$this->sentCount,
            EmailCampaignRecipient::STATUS_FAILED => ++$this->failedCount,
            EmailCampaignRecipient::STATUS_SKIPPED => ++$this->skippedCount,
            default => throw new \InvalidArgumentException('Statut de destinataire marketing inconnu.'),
        };
    }

    private function decrementStatus(string $status): void
    {
        match ($status) {
            EmailCampaignRecipient::STATUS_PENDING => $this->pendingCount = max(0, $this->pendingCount - 1),
            EmailCampaignRecipient::STATUS_SENT => $this->sentCount = max(0, $this->sentCount - 1),
            EmailCampaignRecipient::STATUS_FAILED => $this->failedCount = max(0, $this->failedCount - 1),
            EmailCampaignRecipient::STATUS_SKIPPED => $this->skippedCount = max(0, $this->skippedCount - 1),
            default => throw new \InvalidArgumentException('Statut de destinataire marketing inconnu.'),
        };
    }
}
