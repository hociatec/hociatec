<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderCheckoutSessionRepository::class)]
#[ORM\Table(name: 'order_checkout_sessions')]
#[ORM\HasLifecycleCallbacks]
class OrderCheckoutSession
{
    use OrderCheckoutCustomerTrait;

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

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $itemsPayload = [];

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $orderId = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

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
        $this->stripeSessionId = $stripeSessionId;
        $this->checkoutUrl = $checkoutUrl;
        $this->customerEmail = $user->getEmail();
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
        return $this->stripeSessionId;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getStripePaymentStatus(): ?string
    {
        return $this->stripePaymentStatus;
    }

    public function setStripePaymentStatus(?string $stripePaymentStatus): self
    {
        $this->stripePaymentStatus = $stripePaymentStatus;

        return $this;
    }

    public function getLastStripeEventType(): ?string
    {
        return $this->lastStripeEventType;
    }

    public function setLastStripeEventType(?string $lastStripeEventType): self
    {
        $this->lastStripeEventType = $lastStripeEventType;

        return $this;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    public function setFailureCode(?string $failureCode): self
    {
        $this->failureCode = $failureCode;

        return $this;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function setFailureMessage(?string $failureMessage): self
    {
        $this->failureMessage = $failureMessage;

        return $this;
    }

    public function getCheckoutUrl(): string
    {
        return $this->checkoutUrl;
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

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getSubtotalPriceCents(): int
    {
        return $this->subtotalPriceCents;
    }

    public function setSubtotalPriceCents(int $subtotalPriceCents): self
    {
        $this->subtotalPriceCents = $subtotalPriceCents;

        return $this;
    }

    public function getDiscountAmountCents(): int
    {
        return $this->discountAmountCents;
    }

    public function setDiscountAmountCents(int $discountAmountCents): self
    {
        $this->discountAmountCents = $discountAmountCents;

        return $this;
    }

    public function getTotalPriceCents(): int
    {
        return $this->totalPriceCents;
    }

    public function setTotalPriceCents(int $totalPriceCents): self
    {
        $this->totalPriceCents = $totalPriceCents;

        return $this;
    }

    public function getAppliedPromotionName(): ?string
    {
        return $this->appliedPromotionName;
    }

    public function setAppliedPromotionName(?string $appliedPromotionName): self
    {
        $this->appliedPromotionName = $appliedPromotionName;

        return $this;
    }

    public function getAppliedPromotionSlug(): ?string
    {
        return $this->appliedPromotionSlug;
    }

    public function setAppliedPromotionSlug(?string $appliedPromotionSlug): self
    {
        $this->appliedPromotionSlug = $appliedPromotionSlug;

        return $this;
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
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markPaid(?string $paymentIntentId = null, ?string $paymentStatus = null, ?string $eventType = null): self
    {
        $this->status = self::STATUS_PAID;
        $this->stripePaymentIntentId = $paymentIntentId;
        $this->stripePaymentStatus = $paymentStatus;
        $this->lastStripeEventType = $eventType;
        $this->failureCode = null;
        $this->failureMessage = null;
        $this->completedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markExpired(?string $eventType = null): self
    {
        $this->status = self::STATUS_EXPIRED;
        $this->lastStripeEventType = $eventType;
        $this->updatedAt = new \DateTimeImmutable();

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
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isPendingFulfillment(): bool
    {
        return self::STATUS_OPEN === $this->status || (self::STATUS_PAID === $this->status && null === $this->orderId);
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
