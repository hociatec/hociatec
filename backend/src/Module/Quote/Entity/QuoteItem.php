<?php

declare(strict_types=1);

namespace App\Module\Quote\Entity;

use App\Module\Quote\Repository\QuoteItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteItemRepository::class)]
#[ORM\Table(name: 'quote_items')]
class QuoteItem
{
    public const TYPE_SERVICE = 'service';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_CUSTOM = 'custom';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quote $quote = null;

    #[ORM\Column(length: 20)]
    private string $itemType = self::TYPE_CUSTOM;

    // Optional references to source entities
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $productId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $serviceId = null;

    #[ORM\Column(length: 200)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $unit = null;

    // integer quantity (for simplicity)
    #[ORM\Column(type: 'integer')]
    private int $quantity = 1;

    #[ORM\Column(type: 'integer')]
    private int $unitPriceCents;

    // VAT stored in basis points (e.g. 2000 => 20.00%)
    #[ORM\Column(type: 'integer')]
    private int $vatRateBps = 2000;

    // Optional per-line discount in cents
    #[ORM\Column(type: 'integer')]
    private int $discountCents = 0;

    public function __construct(string $name, int $unitPriceCents)
    {
        $this->name = $name;
        $this->unitPriceCents = $unitPriceCents;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $type): self
    {
        $this->itemType = $type;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $id): self
    {
        $this->productId = $id;

        return $this;
    }

    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    public function setServiceId(?int $id): self
    {
        $this->serviceId = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $qty): self
    {
        $this->quantity = max(1, $qty);

        return $this;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents(int $cents): self
    {
        $this->unitPriceCents = max(0, $cents);

        return $this;
    }

    public function getVatRateBps(): int
    {
        return $this->vatRateBps;
    }

    public function setVatRateBps(int $bps): self
    {
        $this->vatRateBps = max(0, $bps);

        return $this;
    }

    public function getDiscountCents(): int
    {
        return $this->discountCents;
    }

    public function setDiscountCents(int $cents): self
    {
        $this->discountCents = max(0, $cents);

        return $this;
    }
}
