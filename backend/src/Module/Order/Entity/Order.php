<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\Order\Repository\OrderRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const DELIVERY_STATUS_PREPARING = 'preparing';
    public const DELIVERY_STATUS_SHIPPED = 'shipped';
    public const DELIVERY_STATUS_IN_TRANSIT = 'in_transit';
    public const DELIVERY_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERY_STATUS_DELIVERED = 'delivered';
    public const DELIVERY_STATUS_ISSUE = 'issue';

    public const INVOICE_STATUS_ISSUED = 'issued';
    public const INVOICE_STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $number;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'integer')]
    private int $totalPriceCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $subtotalPriceCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $discountAmountCents = 0;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionName = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $appliedPromotionSlug = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $shippingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 30, options: ['default' => self::DELIVERY_STATUS_PREPARING])]
    private string $deliveryStatus = self::DELIVERY_STATUS_PREPARING;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $deliveryCarrier = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $deliveryTrackingNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryTrackingUrl = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deliveryEstimatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deliveryShippedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deliveryDeliveredAt = null;

    #[ORM\Column(length: 30, nullable: true, unique: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 20, options: ['default' => self::INVOICE_STATUS_ISSUED])]
    private string $invoiceStatus = self::INVOICE_STATUS_ISSUED;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $invoicedAt = null;

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

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currencyCode = 'EUR';

    #[ORM\Column(length: 40, options: ['default' => 'UBL-2.1'])]
    private string $electronicFormat = 'UBL-2.1';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoicePdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceXmlPath = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $orderCreatedEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $invoiceEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $statusConfirmedEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $statusDeliveredEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $statusCancelledEmailSentAt = null;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $number, User $user)
    {
        $this->number = $number;
        $this->user = $user;
        $this->items = new ArrayCollection();
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getNumber(): string { return $this->number; }
    public function setNumber(string $number): self { $this->number = $number; return $this; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getTotalPriceCents(): int { return $this->totalPriceCents; }
    public function setTotalPriceCents(int $cents): self { $this->totalPriceCents = max(0, $cents); return $this; }

    public function getSubtotalPriceCents(): int { return $this->subtotalPriceCents; }
    public function setSubtotalPriceCents(int $cents): self { $this->subtotalPriceCents = max(0, $cents); return $this; }

    public function getDiscountAmountCents(): int { return $this->discountAmountCents; }
    public function setDiscountAmountCents(int $cents): self { $this->discountAmountCents = max(0, $cents); return $this; }

    public function getAppliedPromotionName(): ?string { return $this->appliedPromotionName; }
    public function setAppliedPromotionName(?string $name): self { $this->appliedPromotionName = $name; return $this; }

    public function getAppliedPromotionSlug(): ?string { return $this->appliedPromotionSlug; }
    public function setAppliedPromotionSlug(?string $slug): self { $this->appliedPromotionSlug = $slug; return $this; }

    public function getShippingName(): ?string { return $this->shippingName; }
    public function setShippingName(?string $name): self { $this->shippingName = $name; return $this; }

    public function getShippingAddress(): ?string { return $this->shippingAddress; }
    public function setShippingAddress(?string $address): self { $this->shippingAddress = $address; return $this; }

    public function getShippingPostalCode(): ?string { return $this->shippingPostalCode; }
    public function setShippingPostalCode(?string $code): self { $this->shippingPostalCode = $code; return $this; }

    public function getShippingCity(): ?string { return $this->shippingCity; }
    public function setShippingCity(?string $city): self { $this->shippingCity = $city; return $this; }

    public function getDeliveryStatus(): string { return $this->deliveryStatus; }
    public function setDeliveryStatus(string $deliveryStatus): self { $this->deliveryStatus = $deliveryStatus; return $this; }

    public function getDeliveryCarrier(): ?string { return $this->deliveryCarrier; }
    public function setDeliveryCarrier(?string $deliveryCarrier): self { $this->deliveryCarrier = $deliveryCarrier; return $this; }

    public function getDeliveryTrackingNumber(): ?string { return $this->deliveryTrackingNumber; }
    public function setDeliveryTrackingNumber(?string $deliveryTrackingNumber): self { $this->deliveryTrackingNumber = $deliveryTrackingNumber; return $this; }

    public function getDeliveryTrackingUrl(): ?string { return $this->deliveryTrackingUrl; }
    public function setDeliveryTrackingUrl(?string $deliveryTrackingUrl): self { $this->deliveryTrackingUrl = $deliveryTrackingUrl; return $this; }

    public function getDeliveryEstimatedAt(): ?DateTimeImmutable { return $this->deliveryEstimatedAt; }
    public function setDeliveryEstimatedAt(?DateTimeImmutable $deliveryEstimatedAt): self { $this->deliveryEstimatedAt = $deliveryEstimatedAt; return $this; }

    public function getDeliveryShippedAt(): ?DateTimeImmutable { return $this->deliveryShippedAt; }
    public function setDeliveryShippedAt(?DateTimeImmutable $deliveryShippedAt): self { $this->deliveryShippedAt = $deliveryShippedAt; return $this; }

    public function getDeliveryDeliveredAt(): ?DateTimeImmutable { return $this->deliveryDeliveredAt; }
    public function setDeliveryDeliveredAt(?DateTimeImmutable $deliveryDeliveredAt): self { $this->deliveryDeliveredAt = $deliveryDeliveredAt; return $this; }

    public function getInvoiceNumber(): ?string { return $this->invoiceNumber; }
    public function setInvoiceNumber(?string $invoiceNumber): self { $this->invoiceNumber = $invoiceNumber; return $this; }

    public function getInvoiceStatus(): string { return $this->invoiceStatus; }
    public function setInvoiceStatus(string $invoiceStatus): self { $this->invoiceStatus = $invoiceStatus; return $this; }

    public function getInvoicedAt(): ?DateTimeImmutable { return $this->invoicedAt; }
    public function setInvoicedAt(?DateTimeImmutable $invoicedAt): self { $this->invoicedAt = $invoicedAt; return $this; }

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

    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function setCurrencyCode(string $currencyCode): self { $this->currencyCode = $currencyCode; return $this; }

    public function getElectronicFormat(): string { return $this->electronicFormat; }
    public function setElectronicFormat(string $electronicFormat): self { $this->electronicFormat = $electronicFormat; return $this; }

    public function getInvoicePdfPath(): ?string { return $this->invoicePdfPath; }
    public function setInvoicePdfPath(?string $invoicePdfPath): self { $this->invoicePdfPath = $invoicePdfPath; return $this; }

    public function getInvoiceXmlPath(): ?string { return $this->invoiceXmlPath; }
    public function setInvoiceXmlPath(?string $invoiceXmlPath): self { $this->invoiceXmlPath = $invoiceXmlPath; return $this; }

    public function getOrderCreatedEmailSentAt(): ?DateTimeImmutable { return $this->orderCreatedEmailSentAt; }
    public function setOrderCreatedEmailSentAt(?DateTimeImmutable $sentAt): self { $this->orderCreatedEmailSentAt = $sentAt; return $this; }

    public function getInvoiceEmailSentAt(): ?DateTimeImmutable { return $this->invoiceEmailSentAt; }
    public function setInvoiceEmailSentAt(?DateTimeImmutable $sentAt): self { $this->invoiceEmailSentAt = $sentAt; return $this; }

    public function getStatusConfirmedEmailSentAt(): ?DateTimeImmutable { return $this->statusConfirmedEmailSentAt; }
    public function setStatusConfirmedEmailSentAt(?DateTimeImmutable $sentAt): self { $this->statusConfirmedEmailSentAt = $sentAt; return $this; }

    public function getStatusDeliveredEmailSentAt(): ?DateTimeImmutable { return $this->statusDeliveredEmailSentAt; }
    public function setStatusDeliveredEmailSentAt(?DateTimeImmutable $sentAt): self { $this->statusDeliveredEmailSentAt = $sentAt; return $this; }

    public function getStatusCancelledEmailSentAt(): ?DateTimeImmutable { return $this->statusCancelledEmailSentAt; }
    public function setStatusCancelledEmailSentAt(?DateTimeImmutable $sentAt): self { $this->statusCancelledEmailSentAt = $sentAt; return $this; }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
