<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_checkout_sessions')]
#[ORM\HasLifecycleCallbacks]
class OrderCheckoutSession
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cartId = null;

    #[ORM\Column(length: 64)]
    private string $cartToken;

    #[ORM\Column(type: 'integer')]
    private int $shippingAddressId;

    #[ORM\Embedded(class: CheckoutPaymentState::class, columnPrefix: false)]
    private CheckoutPaymentState $payment;

    #[ORM\Embedded(class: CheckoutPricingSnapshot::class, columnPrefix: false)]
    private CheckoutPricingSnapshot $pricing;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerFullName = null;

    #[ORM\Column(length: 180)]
    private string $customerEmail;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $shippingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingCompany = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingCompanySiren = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $billingCompanyVatNumber = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $purchaseOrderNumber = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $billingCity = null;

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $itemsPayload = [];

    #[ORM\Embedded(class: CheckoutLifecycleState::class, columnPrefix: false)]
    private CheckoutLifecycleState $lifecycle;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $token, User $user, string $cartToken, int $shippingAddressId, string $stripeSessionId, string $checkoutUrl)
    {
        $this->token = $token;
        $this->user = $user;
        $this->cartToken = $cartToken;
        $this->shippingAddressId = $shippingAddressId;
        $this->payment = new CheckoutPaymentState($stripeSessionId, $checkoutUrl);
        $this->pricing = new CheckoutPricingSnapshot();
        $this->customerEmail = $user->getEmail();
        $this->lifecycle = new CheckoutLifecycleState();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCartId(): ?int
    {
        return $this->cartId;
    }

    public function setCartId(?int $cartId): self
    {
        $this->cartId = $cartId;

        return $this;
    }

    public function getCartToken(): string
    {
        return $this->cartToken;
    }

    public function getShippingAddressId(): int
    {
        return $this->shippingAddressId;
    }

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
        $this->payment->changeStripePaymentStatus($stripePaymentStatus);

        return $this;
    }

    public function getLastStripeEventType(): ?string
    {
        return $this->payment->lastStripeEventType();
    }

    public function setLastStripeEventType(?string $lastStripeEventType): self
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

    public function getStatus(): string
    {
        return $this->lifecycle->status();
    }

    public function setStatus(string $status): self
    {
        $this->lifecycle->changeStatus($status);

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->pricing->currencyCode();
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->pricing->changeCurrencyCode($currencyCode);

        return $this;
    }

    public function getSubtotalPriceCents(): int
    {
        return $this->pricing->subtotalPriceCents();
    }

    public function setSubtotalPriceCents(int $subtotalPriceCents): self
    {
        $this->pricing->changeSubtotalPriceCents($subtotalPriceCents);

        return $this;
    }

    public function getDiscountAmountCents(): int
    {
        return $this->pricing->discountAmountCents();
    }

    public function setDiscountAmountCents(int $discountAmountCents): self
    {
        $this->pricing->changeDiscountAmountCents($discountAmountCents);

        return $this;
    }

    public function getTotalPriceCents(): int
    {
        return $this->pricing->totalPriceCents();
    }

    public function setTotalPriceCents(int $totalPriceCents): self
    {
        $this->pricing->changeTotalPriceCents($totalPriceCents);

        return $this;
    }

    public function getAppliedPromotionName(): ?string
    {
        return $this->pricing->appliedPromotionName();
    }

    public function setAppliedPromotionName(?string $appliedPromotionName): self
    {
        $this->pricing->changeAppliedPromotionName($appliedPromotionName);

        return $this;
    }

    public function getAppliedPromotionSlug(): ?string
    {
        return $this->pricing->appliedPromotionSlug();
    }

    public function setAppliedPromotionSlug(?string $appliedPromotionSlug): self
    {
        $this->pricing->changeAppliedPromotionSlug($appliedPromotionSlug);

        return $this;
    }

    public function getCustomerFullName(): ?string { return $this->customerSnapshot()->fullName(); }
    public function setCustomerFullName(?string $customerFullName): self { $this->customerFullName = $customerFullName; return $this; }
    public function getCustomerEmail(): string { return $this->customerSnapshot()->email(); }
    public function setCustomerEmail(string $customerEmail): self { $this->customerEmail = $customerEmail; return $this; }
    public function getShippingName(): ?string { return $this->shippingSnapshot()->name(); }
    public function setShippingName(?string $shippingName): self { $this->shippingName = $shippingName; return $this; }
    public function getShippingAddress(): ?string { return $this->shippingSnapshot()->address(); }
    public function setShippingAddress(?string $shippingAddress): self { $this->shippingAddress = $shippingAddress; return $this; }
    public function getShippingPostalCode(): ?string { return $this->shippingSnapshot()->postalCode(); }
    public function setShippingPostalCode(?string $shippingPostalCode): self { $this->shippingPostalCode = $shippingPostalCode; return $this; }
    public function getShippingCity(): ?string { return $this->shippingSnapshot()->city(); }
    public function setShippingCity(?string $shippingCity): self { $this->shippingCity = $shippingCity; return $this; }
    public function getBillingName(): ?string { return $this->billingSnapshot()->name(); }
    public function setBillingName(?string $billingName): self { $this->billingName = $billingName; return $this; }
    public function getBillingCompany(): ?string { return $this->billingSnapshot()->company(); }
    public function setBillingCompany(?string $billingCompany): self { $this->billingCompany = $billingCompany; return $this; }
    public function getBillingCompanySiren(): ?string { return $this->billingSnapshot()->companySiren(); }
    public function setBillingCompanySiren(?string $billingCompanySiren): self { $this->billingCompanySiren = $billingCompanySiren; return $this; }
    public function getBillingCompanyVatNumber(): ?string { return $this->billingSnapshot()->companyVatNumber(); }
    public function setBillingCompanyVatNumber(?string $billingCompanyVatNumber): self { $this->billingCompanyVatNumber = $billingCompanyVatNumber; return $this; }
    public function getPurchaseOrderNumber(): ?string { return $this->billingSnapshot()->purchaseOrderNumber(); }
    public function setPurchaseOrderNumber(?string $purchaseOrderNumber): self { $this->purchaseOrderNumber = $purchaseOrderNumber; return $this; }
    public function getBillingEmail(): ?string { return $this->billingSnapshot()->email(); }
    public function setBillingEmail(?string $billingEmail): self { $this->billingEmail = $billingEmail; return $this; }
    public function getBillingAddress(): ?string { return $this->billingSnapshot()->address(); }
    public function setBillingAddress(?string $billingAddress): self { $this->billingAddress = $billingAddress; return $this; }
    public function getBillingPostalCode(): ?string { return $this->billingSnapshot()->postalCode(); }
    public function setBillingPostalCode(?string $billingPostalCode): self { $this->billingPostalCode = $billingPostalCode; return $this; }
    public function getBillingCity(): ?string { return $this->billingSnapshot()->city(); }
    public function setBillingCity(?string $billingCity): self { $this->billingCity = $billingCity; return $this; }

    private function customerSnapshot(): CheckoutCustomerSnapshot
    {
        return new CheckoutCustomerSnapshot($this->customerFullName, $this->customerEmail);
    }

    private function shippingSnapshot(): CheckoutShippingSnapshot
    {
        return new CheckoutShippingSnapshot($this->shippingName, $this->shippingAddress, $this->shippingPostalCode, $this->shippingCity);
    }

    private function billingSnapshot(): CheckoutBillingSnapshot
    {
        return new CheckoutBillingSnapshot(
            $this->billingName,
            $this->billingCompany,
            $this->billingCompanySiren,
            $this->billingCompanyVatNumber,
            $this->purchaseOrderNumber,
            $this->billingEmail,
            $this->billingAddress,
            $this->billingPostalCode,
            $this->billingCity,
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getItemsPayload(): array
    {
        return $this->itemsPayload;
    }

    /** @param array<int, array<string, mixed>> $itemsPayload */
    public function setItemsPayload(array $itemsPayload): self
    {
        $this->itemsPayload = $itemsPayload;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markPaid(?string $paymentIntentId = null, ?string $paymentStatus = null, ?string $eventType = null): self
    {
        $this->lifecycle->markPaid(new \DateTimeImmutable());
        $this->payment->markPaid($paymentIntentId, $paymentStatus, $eventType);
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

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
