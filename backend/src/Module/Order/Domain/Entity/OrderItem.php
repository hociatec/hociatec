<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Entity\Concern\OrderItemPricingConcern;
use App\Module\Order\Domain\Entity\Concern\OrderItemRentalLifecycleConcern;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    use OrderItemPricingConcern;
    use OrderItemRentalLifecycleConcern;

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

    #[ORM\Column(length: 10, options: ['default' => 'sale'])]
    private string $sellingType = 'sale';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalMonths = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalStartDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalEndDate = null;

    #[ORM\Column(length: 20, options: ['default' => 'none'])]
    private string $rentalRequestStatus = 'none';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $rentalRequestType = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalRequestedEndDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalRequestCreatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalRequestUpdatedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalOriginOrderItemId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalExtensionOrderId = null;

    #[ORM\Column(length: 30, options: ['default' => 'none'])]
    private string $rentalReturnStatus = 'none';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $rentalReturnMode = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalReturnRequestedDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalReturnRequestedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalReturnCompletedAt = null;

    public function __construct(string $productName, string $productSku, int $unitPriceCents, int $quantity)
    {
        $this->productName = $productName;
        $this->productSku = $productSku;
        $this->unitPriceCents = Money::fromCents($unitPriceCents)->cents();
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantité doit être supérieure ou égale à 1.');
        }
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
}
