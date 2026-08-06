<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class CheckoutPaymentState
{
    #[ORM\Column(length: 255, unique: true)]
    private string $stripeSessionId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 40, nullable: true, enumType: StripePaymentStatus::class)]
    private ?StripePaymentStatus $stripePaymentStatus = null;

    #[ORM\Column(length: 80, nullable: true, enumType: StripeCheckoutEventType::class)]
    private ?StripeCheckoutEventType $lastStripeEventType = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $failureCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureMessage = null;

    #[ORM\Column(type: 'text')]
    private string $checkoutUrl;

    public function __construct(string $stripeSessionId, string $checkoutUrl)
    {
        $this->stripeSessionId = $stripeSessionId;
        $this->checkoutUrl = $checkoutUrl;
    }

    public function stripeSessionId(): string
    {
        return $this->stripeSessionId;
    }

    public function stripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function changeStripePaymentIntentId(?string $paymentIntentId): void
    {
        $this->stripePaymentIntentId = $paymentIntentId;
    }

    public function stripePaymentStatus(): ?string
    {
        return $this->stripePaymentStatus?->value;
    }

    public function changeStripePaymentStatus(?string $paymentStatus): void
    {
        $this->stripePaymentStatus = null !== $paymentStatus ? StripePaymentStatus::fromInput($paymentStatus) : null;
    }

    public function lastStripeEventType(): ?string
    {
        return $this->lastStripeEventType?->value;
    }

    public function changeLastStripeEventType(?string $eventType): void
    {
        $this->lastStripeEventType = null !== $eventType ? StripeCheckoutEventType::fromInput($eventType) : null;
    }

    public function failureCode(): ?string
    {
        return $this->failureCode;
    }

    public function changeFailureCode(?string $failureCode): void
    {
        $this->failureCode = $failureCode;
    }

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function changeFailureMessage(?string $failureMessage): void
    {
        $this->failureMessage = $failureMessage;
    }

    public function checkoutUrl(): string
    {
        return $this->checkoutUrl;
    }

    public function markPaid(?string $paymentIntentId, ?string $paymentStatus, ?string $eventType): void
    {
        $this->stripePaymentIntentId = $paymentIntentId;
        $this->changeStripePaymentStatus($paymentStatus);
        $this->changeLastStripeEventType($eventType);
        $this->failureCode = null;
        $this->failureMessage = null;
    }

    public function markExpired(?string $eventType): void
    {
        $this->changeLastStripeEventType($eventType);
    }

    public function markFailed(?string $paymentIntentId, ?string $paymentStatus, ?string $eventType, ?string $failureCode, ?string $failureMessage): void
    {
        $this->stripePaymentIntentId = $paymentIntentId ?? $this->stripePaymentIntentId;
        $this->changeStripePaymentStatus($paymentStatus);
        $this->changeLastStripeEventType($eventType);
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
    }
}
