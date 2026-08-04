<?php

declare(strict_types=1);

namespace App\Module\Audit\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_requests')]
#[ORM\HasLifecycleCallbacks]
class AuditRequest
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_DONE = 'done';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $number;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $client;

    #[ORM\Column(enumType: AuditType::class)]
    private AuditType $type;

    #[ORM\Column(length: 255)]
    private string $targetUrl;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $objectives = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_NEW;

    /** @var Collection<int, AuditChecklistItem> */
    #[ORM\OneToMany(mappedBy: 'audit', targetEntity: AuditChecklistItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $number, User $client, AuditType $type, string $targetUrl, ?string $objectives)
    {
        $this->number = $number;
        $this->client = $client;
        $this->type = $type;
        $this->targetUrl = $targetUrl;
        $this->objectives = $objectives;
        $this->items = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getClient(): User
    {
        return $this->client;
    }

    public function getType(): AuditType
    {
        return $this->type;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getObjectives(): ?string
    {
        return $this->objectives;
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

    /** @return Collection<int, AuditChecklistItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(AuditChecklistItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setAudit($this);
        }

        return $this;
    }

    public function removeItem(AuditChecklistItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getAudit() === $this) {
                $item->setAudit(null);
            }
        }

        return $this;
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
}
