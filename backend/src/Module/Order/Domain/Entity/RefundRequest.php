<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\Enum\RefundStatus;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refund_requests')]
#[ORM\HasLifecycleCallbacks]
class RefundRequest
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $paymentId = null;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(length: 3)]
    private string $currencyCode = 'EUR';

    #[ORM\Column(length: 40, enumType: RefundStatus::class)]
    private RefundStatus $status = RefundStatus::REQUESTED;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $internalNotes = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $stripeRefundId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Order $order, int $amountCents, ?User $actor = null)
    {
        if ($amountCents < 0) {
            throw new \InvalidArgumentException('Le montant du remboursement ne peut pas être négatif.');
        }

        $this->order = $order;
        $this->amountCents = $amountCents;
        $this->actor = $actor;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function setPaymentId(?int $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): self
    {
        if ($amountCents < 0) {
            throw new \InvalidArgumentException('Le montant du remboursement ne peut pas être négatif.');
        }

        $this->amountCents = $amountCents;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = strtoupper(substr($currencyCode, 0, 3));

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function setStatus(string $status): self
    {
        $this->status = RefundStatus::from($status);

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = null !== $reason ? trim($reason) : null;

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

    public function getStripeRefundId(): ?string
    {
        return $this->stripeRefundId;
    }

    public function setStripeRefundId(?string $stripeRefundId): self
    {
        $this->stripeRefundId = null !== $stripeRefundId ? trim($stripeRefundId) : null;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
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
