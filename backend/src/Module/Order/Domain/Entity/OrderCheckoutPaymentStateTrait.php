<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderCheckoutPaymentStateTrait
{
    public function getStripeSessionId(): string
    {
        return $this->payment->stripeSessionId();
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->payment->stripePaymentIntentId();
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->payment->changeStripePaymentIntentId($stripePaymentIntentId);

        return $this;
    }

    public function getStripePaymentStatus(): ?string
    {
        return $this->payment->stripePaymentStatus();
    }

    public function setStripePaymentStatus(?string $stripePaymentStatus): self
    {
        return $this->recordStripePaymentStatus($stripePaymentStatus);
    }

    public function recordStripePaymentStatus(?string $stripePaymentStatus): self
    {
        $this->payment->changeStripePaymentStatus($stripePaymentStatus);

        return $this;
    }

    public function getLastStripeEventType(): ?string
    {
        return $this->payment->lastStripeEventType();
    }

    public function setLastStripeEventType(?string $lastStripeEventType): self
    {
        return $this->recordStripeEvent($lastStripeEventType);
    }

    public function recordStripeEvent(?string $lastStripeEventType): self
    {
        $this->payment->changeLastStripeEventType($lastStripeEventType);

        return $this;
    }

    public function getFailureCode(): ?string
    {
        return $this->payment->failureCode();
    }

    public function setFailureCode(?string $failureCode): self
    {
        $this->payment->changeFailureCode($failureCode);

        return $this;
    }

    public function getFailureMessage(): ?string
    {
        return $this->payment->failureMessage();
    }

    public function setFailureMessage(?string $failureMessage): self
    {
        $this->payment->changeFailureMessage($failureMessage);

        return $this;
    }

    public function getCheckoutUrl(): string
    {
        return $this->payment->checkoutUrl();
    }
}
