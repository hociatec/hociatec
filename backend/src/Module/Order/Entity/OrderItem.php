<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\Catalog\Entity\Product;
use App\Module\Order\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Shared\Value\Money;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 180)]
    private string $productName;

    #[ORM\Column(length: 60)]
    private string $productSku;

    #[ORM\Column(type: 'integer')]
    private int $unitPriceCents;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function __construct(string $productName, string $productSku, int $unitPriceCents, int $quantity)
    {
        $this->productName = $productName;
        $this->productSku = $productSku;
        $this->unitPriceCents = $unitPriceCents;
        $this->quantity = $quantity;
    }

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): self { $this->order = $order; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): self { $this->product = $product; return $this; }

    public function getProductName(): string { return $this->productName; }
    public function getProductSku(): string { return $this->productSku; }
    public function getUnitPriceCents(): int { return $this->unitPriceCents; }
    public function getQuantity(): int { return $this->quantity; }

    public function getLinePriceCents(): int { return $this->unitPriceCents * $this->quantity; }

    public function getUnitPriceMoney(string $currency = 'EUR'): Money
    {
        return Money::ofCents($this->unitPriceCents, $currency);
    }

    public function getLinePriceMoney(string $currency = 'EUR'): Money
    {
        return $this->getUnitPriceMoney($currency)->multiply($this->quantity);
    }
}
