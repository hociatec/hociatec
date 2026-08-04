<?php

declare(strict_types=1);

namespace App\Module\Support\Domain\Entity;

use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'support_requests')]
#[ORM\HasLifecycleCallbacks]
class SupportRequest
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REFUSED = 'refused';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $customer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $order = null;

    #[ORM\Column(length: 40)]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(length: 80)]
    private string $reason = 'other';

    #[ORM\Column(length: 180)]
    private string $subject;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $internalNotes = null;

    /** @var list<array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $attachments = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct(User $customer, string $subject)
    {
        $this->customer = $customer;
        $this->subject = '' !== trim($subject) ? trim($subject) : 'Demande SAV';
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        if (in_array($status, [self::STATUS_RESOLVED, self::STATUS_REFUSED], true) && null === $this->resolvedAt) {
            $this->resolvedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = '' !== trim($reason) ? trim($reason) : 'other';

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = '' !== trim($subject) ? trim($subject) : $this->subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = null !== $message ? trim($message) : null;

        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): self
    {
        $this->internalNotes = null !== $internalNotes ? trim($internalNotes) : null;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /** @param list<array<string, mixed>> $attachments */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;

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

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
