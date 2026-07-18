<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderCheckoutSessionRepository::class)]
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

    #[ORM\Column(length: 255, unique: true)]
    private string $stripeSessionId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $stripePaymentStatus = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $lastStripeEventType = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $failureCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureMessage = null;

    #[ORM\Column(type: 'text')]
    private string $checkoutUrl;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 3)]
    private string $currencyCode = 'EUR';

    #[ORM\Column(type: 'integer')]
    private int $subtotalPriceCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $discountAmountCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalPriceCents = 0;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionName = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionSlug = null;

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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $orderId = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $token, User $user, string $cartToken, int $shippingAddressId, string $stripeSessionId, string $checkoutUrl)
    {
        $this->token = $token;
        $this->user = $user;
        $this->cartToken = $cartToken;
        $this->shippingAddressId = $shippingAddressId;
        $this->stripeSessionId = $stripeSessionId;
        $this->checkoutUrl = $checkoutUrl;
        $this->customerEmail = $user->getEmail();
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function getUser(): User { return $this->user; }
    public function getCartId(): ?int { return $this->cartId; }
    public function setCartId(?int $cartId): self { $this->cartId = $cartId; return $this; }
    public function getCartToken(): string { return $this->cartToken; }
    public function getShippingAddressId(): int { return $this->shippingAddressId; }
    public function getStripeSessionId(): string { return $this->stripeSessionId; }
    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self { $this->stripePaymentIntentId = $stripePaymentIntentId; return $this; }
    public function getStripePaymentStatus(): ?string { return $this->stripePaymentStatus; }
    public function setStripePaymentStatus(?string $stripePaymentStatus): self { $this->stripePaymentStatus = $stripePaymentStatus; return $this; }
    public function getLastStripeEventType(): ?string { return $this->lastStripeEventType; }
    public function setLastStripeEventType(?string $lastStripeEventType): self { $this->lastStripeEventType = $lastStripeEventType; return $this; }
    public function getFailureCode(): ?string { return $this->failureCode; }
    public function setFailureCode(?string $failureCode): self { $this->failureCode = $failureCode; return $this; }
    public function getFailureMessage(): ?string { return $this->failureMessage; }
    public function setFailureMessage(?string $failureMessage): self { $this->failureMessage = $failureMessage; return $this; }
    public function getCheckoutUrl(): string { return $this->checkoutUrl; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function setCurrencyCode(string $currencyCode): self { $this->currencyCode = $currencyCode; return $this; }
    public function getSubtotalPriceCents(): int { return $this->subtotalPriceCents; }
    public function setSubtotalPriceCents(int $subtotalPriceCents): self { $this->subtotalPriceCents = $subtotalPriceCents; return $this; }
    public function getDiscountAmountCents(): int { return $this->discountAmountCents; }
    public function setDiscountAmountCents(int $discountAmountCents): self { $this->discountAmountCents = $discountAmountCents; return $this; }
    public function getTotalPriceCents(): int { return $this->totalPriceCents; }
    public function setTotalPriceCents(int $totalPriceCents): self { $this->totalPriceCents = $totalPriceCents; return $this; }
    public function getAppliedPromotionName(): ?string { return $this->appliedPromotionName; }
    public function setAppliedPromotionName(?string $appliedPromotionName): self { $this->appliedPromotionName = $appliedPromotionName; return $this; }
    public function getAppliedPromotionSlug(): ?string { return $this->appliedPromotionSlug; }
    public function setAppliedPromotionSlug(?string $appliedPromotionSlug): self { $this->appliedPromotionSlug = $appliedPromotionSlug; return $this; }
    public function getCustomerFullName(): ?string { return $this->customerFullName; }
    public function setCustomerFullName(?string $customerFullName): self { $this->customerFullName = $customerFullName; return $this; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function setCustomerEmail(string $customerEmail): self { $this->customerEmail = $customerEmail; return $this; }
    public function getShippingName(): ?string { return $this->shippingName; }
    public function setShippingName(?string $shippingName): self { $this->shippingName = $shippingName; return $this; }
    public function getShippingAddress(): ?string { return $this->shippingAddress; }
    public function setShippingAddress(?string $shippingAddress): self { $this->shippingAddress = $shippingAddress; return $this; }
    public function getShippingPostalCode(): ?string { return $this->shippingPostalCode; }
    public function setShippingPostalCode(?string $shippingPostalCode): self { $this->shippingPostalCode = $shippingPostalCode; return $this; }
    public function getShippingCity(): ?string { return $this->shippingCity; }
    public function setShippingCity(?string $shippingCity): self { $this->shippingCity = $shippingCity; return $this; }
    public function getBillingName(): ?string { return $this->billingName; }
    public function setBillingName(?string $billingName): self { $this->billingName = $billingName; return $this; }
    public function getBillingCompany(): ?string { return $this->billingCompany; }
    public function setBillingCompany(?string $billingCompany): self { $this->billingCompany = $billingCompany; return $this; }
    public function getBillingCompanySiren(): ?string { return $this->billingCompanySiren; }
    public function setBillingCompanySiren(?string $billingCompanySiren): self { $this->billingCompanySiren = $billingCompanySiren; return $this; }
    public function getBillingCompanyVatNumber(): ?string { return $this->billingCompanyVatNumber; }
    public function setBillingCompanyVatNumber(?string $billingCompanyVatNumber): self { $this->billingCompanyVatNumber = $billingCompanyVatNumber; return $this; }
    public function getPurchaseOrderNumber(): ?string { return $this->purchaseOrderNumber; }
    public function setPurchaseOrderNumber(?string $purchaseOrderNumber): self { $this->purchaseOrderNumber = $purchaseOrderNumber; return $this; }
    public function getBillingEmail(): ?string { return $this->billingEmail; }
    public function setBillingEmail(?string $billingEmail): self { $this->billingEmail = $billingEmail; return $this; }
    public function getBillingAddress(): ?string { return $this->billingAddress; }
    public function setBillingAddress(?string $billingAddress): self { $this->billingAddress = $billingAddress; return $this; }
    public function getBillingPostalCode(): ?string { return $this->billingPostalCode; }
    public function setBillingPostalCode(?string $billingPostalCode): self { $this->billingPostalCode = $billingPostalCode; return $this; }
    public function getBillingCity(): ?string { return $this->billingCity; }
    public function setBillingCity(?string $billingCity): self { $this->billingCity = $billingCity; return $this; }
    /** @return array<int, array<string, mixed>> */
    public function getItemsPayload(): array { return $this->itemsPayload; }
    /** @param array<int, array<string, mixed>> $itemsPayload */
    public function setItemsPayload(array $itemsPayload): self { $this->itemsPayload = $itemsPayload; return $this; }
    public function getOrderId(): ?int { return $this->orderId; }
    public function setOrderId(?int $orderId): self { $this->orderId = $orderId; return $this; }
    public function getCompletedAt(): ?DateTimeImmutable { return $this->completedAt; }
    public function getExpiresAt(): ?DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(?DateTimeImmutable $expiresAt): self { $this->expiresAt = $expiresAt; return $this; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function markPaid(?string $paymentIntentId = null, ?string $paymentStatus = null, ?string $eventType = null): self
    {
        $this->status = self::STATUS_PAID;
        $this->stripePaymentIntentId = $paymentIntentId;
        $this->stripePaymentStatus = $paymentStatus;
        $this->lastStripeEventType = $eventType;
        $this->failureCode = null;
        $this->failureMessage = null;
        $this->completedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function markExpired(?string $eventType = null): self
    {
        $this->status = self::STATUS_EXPIRED;
        $this->lastStripeEventType = $eventType;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function markFailed(?string $paymentIntentId = null, ?string $paymentStatus = null, ?string $eventType = null, ?string $failureCode = null, ?string $failureMessage = null): self
    {
        $this->status = self::STATUS_FAILED;
        $this->stripePaymentIntentId = $paymentIntentId ?? $this->stripePaymentIntentId;
        $this->stripePaymentStatus = $paymentStatus;
        $this->lastStripeEventType = $eventType;
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function isPendingFulfillment(): bool
    {
        return $this->status === self::STATUS_OPEN || ($this->status === self::STATUS_PAID && $this->orderId === null);
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
