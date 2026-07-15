<?php

declare(strict_types=1);

namespace App\Module\Cart\Entity;

use App\Module\Cart\Repository\CartSessionRepository;
use App\Module\Catalog\Entity\Product;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartSessionRepository::class)]
#[ORM\Table(name: 'cart_sessions')]
#[ORM\HasLifecycleCallbacks]
class CartSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'promotion_code', length: 64, nullable: true)]
    private ?string $voucherCode = null;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->items = new ArrayCollection();

        $now = new DateTimeImmutable();
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

    /**
     * @return Collection<int, CartItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
            $this->touch();
        }

        return $this;
    }

    public function removeItem(CartItem $item): self
    {
        if ($this->items->removeElement($item)) {
            $this->touch();
        }

        return $this;
    }

    public function hasProduct(Product $product): bool
    {
        return $this->getItemForProduct($product) !== null;
    }

    public function getItemForProduct(Product $product, ?int $rentalMonths = null): ?CartItem
    {
        $firstMatch = null;

        foreach ($this->items as $item) {
            if ($item->getProduct() !== $product) {
                continue;
            }

            if ($product->getSellingType() !== 'rental') {
                return $item;
            }

            if ($rentalMonths === null) {
                $firstMatch ??= $item;
                continue;
            }

            if ($item->getRentalMonths() === $rentalMonths) {
                return $item;
            }
        }

        return $firstMatch;
    }

    /**
     * @return list<CartItem>
     */
    public function getItemsForProduct(Product $product): array
    {
        $matches = [];

        foreach ($this->items as $item) {
            if ($item->getProduct() === $product) {
                $matches[] = $item;
            }
        }

        return $matches;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }

    public function setVoucherCode(?string $voucherCode): self
    {
        if ($voucherCode === null) {
            $this->voucherCode = null;
            $this->touch();

            return $this;
        }

        $normalized = trim($voucherCode);
        $this->voucherCode = $normalized !== '' ? mb_strtoupper($normalized) : null;
        $this->touch();

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
