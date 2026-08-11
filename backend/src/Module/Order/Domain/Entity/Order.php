<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'idx_orders_status_created', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'idx_orders_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_orders_invoiced_at', columns: ['invoiced_at'])]
#[ORM\HasLifecycleCallbacks]
class Order
{
    use OrderBillingTrait;
    use OrderDeliveryTrait;
    use OrderEmailStateTrait;
    use OrderInvoiceDocumentsTrait;
    use OrderInvoiceStateTrait;
    use OrderPaymentTrait;
    use OrderStatusTrait;

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

    #[ORM\Embedded(class: OrderStatusState::class, columnPrefix: false)]
    private OrderStatusState $state;

    #[ORM\Embedded(class: OrderPayment::class, columnPrefix: false)]
    private OrderPayment $payment;

    #[ORM\Embedded(class: OrderDelivery::class, columnPrefix: false)]
    private OrderDelivery $delivery;

    #[ORM\Embedded(class: OrderBilling::class, columnPrefix: false)]
    private OrderBilling $billing;

    #[ORM\Embedded(class: OrderInvoice::class, columnPrefix: false)]
    private OrderInvoice $invoice;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $number, User $user)
    {
        $this->number = $number;
        $this->user = $user;
        $this->state = new OrderStatusState();
        $this->payment = new OrderPayment();
        $this->delivery = new OrderDelivery();
        $this->billing = new OrderBilling();
        $this->invoice = new OrderInvoice();
        $this->items = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function anonymizePersonalData(): self
    {
        $this->setBillingName('Deleted user');
        $this->setBillingEmail(null);
        $this->setBillingAddress(null);
        $this->setBillingPostalCode(null);
        $this->setBillingCity(null);
        $this->setShippingName('Deleted user');
        $this->setShippingAddress(null);
        $this->setShippingPostalCode(null);
        $this->setShippingCity(null);

        return $this;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
