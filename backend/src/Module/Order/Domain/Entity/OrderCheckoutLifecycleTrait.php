<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderCheckoutLifecycleTrait
{
    public function getStatus(): string
    {
        return $this->lifecycle->status();
    }

    public function setStatus(string $status): self
    {
        return $this->changeCheckoutStatus(CheckoutStatus::from($status));
    }

    public function changeCheckoutStatus(CheckoutStatus $status): self
    {
        $this->lifecycle->changeStatus($status->value);

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->lifecycle->orderId();
    }

    public function setOrderId(?int $orderId): self
    {
        $this->lifecycle->changeOrderId($orderId);

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->lifecycle->completedAt();
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->lifecycle->expiresAt();
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->lifecycle->changeExpiresAt($expiresAt);

        return $this;
    }

    public function markPaid(?string $paymentIntentId = null, ?string $paymentStatus = null, ?string $eventType = null): self
    {
        $this->lifecycle->markPaid(new \DateTimeImmutable());
        $this->payment->markPaid($paymentIntentId, $paymentStatus, $eventType);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function completeWithOrder(int $orderId): self
    {
        $this->lifecycle->completeWithOrder($orderId, new \DateTimeImmutable());
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markExpired(?string $eventType = null): self
    {
        $this->lifecycle->markExpired();
        $this->payment->markExpired($eventType);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markFailed(?string $paymentIntentId = null, ?string $paymentStatus = null, ?string $eventType = null, ?string $failureCode = null, ?string $failureMessage = null): self
    {
        $this->lifecycle->markFailed();
        $this->payment->markFailed($paymentIntentId, $paymentStatus, $eventType, $failureCode, $failureMessage);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isPendingFulfillment(): bool
    {
        return self::STATUS_OPEN === $this->lifecycle->status()
            || (self::STATUS_PAID === $this->lifecycle->status() && null === $this->lifecycle->orderId());
    }
}
