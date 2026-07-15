<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\User\Entity\User;
use App\Module\Order\Repository\OrderRepository;
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
