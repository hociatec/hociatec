<?php

declare(strict_types=1);

namespace App\Module\Order\Entity;

use App\Module\Catalog\Entity\Product;
use App\Module\Order\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\Column(type: 'integer', options: ['default' => 2000])]
    private int $vatRateBps = 2000;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lineSubtotalCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lineVatCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lineTotalCents = 0;

    public function __construct(string $productName, string $productSku, int $unitPriceCents, int $quantity)
    {
        $this->productName = $productName;
        $this->productSku = $productSku;
        $this->unitPriceCents = $unitPriceCents;
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getVatRateBps(): int
    {
        return $this->vatRateBps;
    }

    public function setVatRateBps(int $vatRateBps): self
    {
        $this->vatRateBps = max(0, $vatRateBps);

        return $this;
    }

    public function getLineSubtotalCents(): int
    {
        return $this->lineSubtotalCents;
    }

    public function setLineSubtotalCents(int $lineSubtotalCents): self
    {
        $this->lineSubtotalCents = max(0, $lineSubtotalCents);

        return $this;
    }

    public function getLineVatCents(): int
    {
        return $this->lineVatCents;
    }

    public function setLineVatCents(int $lineVatCents): self
    {
        $this->lineVatCents = max(0, $lineVatCents);

        return $this;
    }

    public function getLineTotalCents(): int
    {
        return $this->lineTotalCents;
    }

    public function setLineTotalCents(int $lineTotalCents): self
    {
        $this->lineTotalCents = max(0, $lineTotalCents);

        return $this;
    }

    public function getLinePriceCents(): int
    {
        return $this->lineTotalCents > 0 ? $this->lineTotalCents : $this->unitPriceCents * $this->quantity;
    }
}
