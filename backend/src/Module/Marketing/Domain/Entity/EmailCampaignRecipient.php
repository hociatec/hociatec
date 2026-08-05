<?php

declare(strict_types=1);

namespace App\Module\Marketing\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'marketing_email_campaign_recipients')]
#[ORM\UniqueConstraint(name: 'uniq_marketing_campaign_recipient_user', columns: ['campaign_id', 'user_id'])]
#[ORM\HasLifecycleCallbacks]
class EmailCampaignRecipient
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EmailCampaign::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private EmailCampaign $campaign;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 180)]
    private string $emailSnapshot;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $failedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $skippedAt = null;

    private function __construct(EmailCampaign $campaign, User $user, string $status, ?string $failureReason = null)
    {
        $this->campaign = $campaign;
        $this->user = $user;
        $this->emailSnapshot = $user->getEmail();
        $this->status = $status;
        $this->failureReason = $failureReason;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;

        if (self::STATUS_SKIPPED === $status) {
            $this->skippedAt = $now;
        }

        $campaign->registerRecipientStatus($status);
    }

    public static function pending(EmailCampaign $campaign, User $user): self
    {
        return new self($campaign, $user, self::STATUS_PENDING);
    }

    public static function skipped(EmailCampaign $campaign, User $user, string $reason): self
    {
        return new self($campaign, $user, self::STATUS_SKIPPED, self::normalizeReason($reason));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): EmailCampaign
    {
        return $this->campaign;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEmailSnapshot(): string
    {
        return $this->emailSnapshot;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function canAttemptDelivery(): bool
    {
        return self::STATUS_PENDING === $this->status || self::STATUS_FAILED === $this->status;
    }

    public function markSent(): void
    {
        $previous = $this->status;
        $this->status = self::STATUS_SENT;
        $this->failureReason = null;
        $this->sentAt = new \DateTimeImmutable();
        $this->updatedAt = $this->sentAt;
        $this->campaign->transitionRecipientStatus($previous, self::STATUS_SENT);
    }

    public function markFailed(string $reason): void
    {
        $previous = $this->status;
        $this->status = self::STATUS_FAILED;
        $this->failureReason = self::normalizeReason($reason);
        $this->failedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->failedAt;
        $this->campaign->transitionRecipientStatus($previous, self::STATUS_FAILED);
    }

    public function markSkipped(string $reason): void
    {
        $previous = $this->status;
        $this->status = self::STATUS_SKIPPED;
        $this->failureReason = self::normalizeReason($reason);
        $this->skippedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->skippedAt;
        $this->campaign->transitionRecipientStatus($previous, self::STATUS_SKIPPED);
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getSkippedAt(): ?\DateTimeImmutable
    {
        return $this->skippedAt;
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

    private static function normalizeReason(string $reason): string
    {
        return mb_substr($reason, 0, 1000);
    }
}
