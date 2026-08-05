<?php

declare(strict_types=1);

namespace App\Module\Cart\Domain\Entity;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cart_sessions')]
#[ORM\HasLifecycleCallbacks]
class CartSession
{
    private const EXPIRATION_INTERVAL = 'P30D';

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
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'promotion_code', length: 64, nullable: true)]
    private ?string $voucherCode = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $convertedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $convertedOrderId = null;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->items = new ArrayCollection();

        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->expiresAt = $now->add(new \DateInterval(self::EXPIRATION_INTERVAL));
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
        return null !== $this->getItemForProduct($product);
    }

    public function getItemForProduct(Product $product, ?int $rentalMonths = null): ?CartItem
    {
        $firstMatch = null;

        foreach ($this->items as $item) {
            if ($item->getProduct() !== $product) {
                continue;
            }

            if ('rental' !== $product->getSellingType()) {
                return $item;
            }

            if (null === $rentalMonths) {
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt <= ($now ?? new \DateTimeImmutable());
    }

    public function touch(): void
    {
        $now = new \DateTimeImmutable();
        $this->updatedAt = $now;
        $this->expiresAt = $now->add(new \DateInterval(self::EXPIRATION_INTERVAL));
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
        if (null === $voucherCode) {
            $this->voucherCode = null;
            $this->touch();

            return $this;
        }

        $normalized = trim($voucherCode);
        $this->voucherCode = '' !== $normalized ? mb_strtoupper($normalized) : null;
        $this->touch();

        return $this;
    }

    public function getConvertedAt(): ?\DateTimeImmutable
    {
        return $this->convertedAt;
    }

    public function getConvertedOrderId(): ?int
    {
        return $this->convertedOrderId;
    }

    public function isConverted(): bool
    {
        return null !== $this->convertedAt;
    }

    public function markConverted(int $orderId): self
    {
        $this->convertedAt = new \DateTimeImmutable();
        $this->convertedOrderId = $orderId;
        $this->touch();

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->expiresAt = $now->add(new \DateInterval(self::EXPIRATION_INTERVAL));
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->touch();
    }
}
