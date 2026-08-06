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

    public function __construct(mixed ...$values)
    {
        $keys = ['name', 'segmentKey', 'criteria', 'subjectSnapshot', 'htmlSnapshot', 'textSnapshot', 'recipientsCount', 'createdByEmail', 'template'];
        $data = array_fill_keys($keys, null);
        $data['criteria'] = [];
        $data['recipientsCount'] = 0;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $data[$keys[$index]] = $value;
            }
        }
        $data = array_replace($data, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
        $this->name = (string) $data['name'];
        $this->segmentKey = (string) $data['segmentKey'];
        $this->criteria = is_array($data['criteria']) ? $data['criteria'] : [];
        $this->subjectSnapshot = (string) $data['subjectSnapshot'];
        $this->htmlSnapshot = (string) $data['htmlSnapshot'];
        $this->textSnapshot = $data['textSnapshot'];
        $this->recipientsCount = (int) $data['recipientsCount'];
        if ($this->recipientsCount < 0) {
            throw new \InvalidArgumentException('Le nombre de destinataires ne peut pas être négatif.');
        }
        $this->createdByEmail = $data['createdByEmail'];
        $this->template = $data['template'] instanceof EmailTemplate ? $data['template'] : null;
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
        if ($recipientsCount < 0) {
            throw new \InvalidArgumentException('Le nombre de destinataires ne peut pas être négatif.');
        }

        $this->recipientsCount = $recipientsCount;
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
            if (0 === $this->recipientsCount) {
                throw new \LogicException('Le nombre de destinataires ne peut pas devenir négatif.');
            }

            --$this->recipientsCount;
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
            EmailCampaignRecipient::STATUS_PENDING => $this->decrementCount($this->pendingCount),
            EmailCampaignRecipient::STATUS_SENT => $this->decrementCount($this->sentCount),
            EmailCampaignRecipient::STATUS_FAILED => $this->decrementCount($this->failedCount),
            EmailCampaignRecipient::STATUS_SKIPPED => $this->decrementCount($this->skippedCount),
            default => throw new \InvalidArgumentException('Statut de destinataire marketing inconnu.'),
        };
    }

    private function decrementCount(int &$count): void
    {
        if (0 === $count) {
            throw new \LogicException('Un compteur de destinataires ne peut pas devenir négatif.');
        }

        --$count;
    }
}
