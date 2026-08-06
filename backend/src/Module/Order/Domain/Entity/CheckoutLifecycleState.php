<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CheckoutLifecycleState
{
    #[ORM\Column(length: 20, enumType: CheckoutStatus::class)]
    private CheckoutStatus $status = CheckoutStatus::Open;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $orderId = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function status(): string
    {
        return $this->status->value;
    }

    public function changeStatus(string $status): void
    {
        $this->status = CheckoutStatus::from($status);
    }

    public function statusEnum(): CheckoutStatus
    {
        return $this->status;
    }

    public function orderId(): ?int
    {
        return $this->orderId;
    }

    public function changeOrderId(?int $orderId): void
    {
        if (null !== $orderId && $orderId <= 0) {
            throw new \InvalidArgumentException('Identifiant de commande invalide.');
        }

        $this->orderId = $orderId;
    }

    public function completeWithOrder(int $orderId, \DateTimeImmutable $completedAt): void
    {
        $this->changeOrderId($orderId);
        $this->markPaid($completedAt);
    }

    public function completedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function changeExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function markPaid(\DateTimeImmutable $completedAt): void
    {
        $this->status = CheckoutStatus::Paid;
        $this->completedAt = $completedAt;
    }

    public function markExpired(): void
    {
        $this->status = CheckoutStatus::Expired;
    }

    public function markFailed(): void
    {
        $this->status = CheckoutStatus::Failed;
    }
}
