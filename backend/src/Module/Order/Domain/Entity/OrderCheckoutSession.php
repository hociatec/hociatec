<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\ValueObject\CheckoutShippingAddress;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_checkout_sessions')]
#[ORM\HasLifecycleCallbacks]
class OrderCheckoutSession
{
    use OrderCheckoutBillingSnapshotTrait;
    use OrderCheckoutCustomerSnapshotTrait;
    use OrderCheckoutLifecycleTrait;
    use OrderCheckoutPaymentStateTrait;
    use OrderCheckoutPricingStateTrait;
    use OrderCheckoutShippingSnapshotTrait;

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

    private function customerSnapshot(): CheckoutCustomerSnapshot
    {
        return new CheckoutCustomerSnapshot($this->customerFullName, $this->customerEmail);
    }

    private function shippingSnapshot(): CheckoutShippingSnapshot
    {
        return new CheckoutShippingSnapshot(
            new CheckoutShippingAddress(
                $this->shippingName,
                $this->shippingAddress,
                $this->shippingPostalCode,
                $this->shippingCity,
            ),
        );
    }

    private function billingSnapshot(): CheckoutBillingSnapshot
    {
        return CheckoutBillingSnapshot::fromScalars(
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
