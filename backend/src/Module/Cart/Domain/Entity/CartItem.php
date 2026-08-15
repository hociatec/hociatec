<?php

declare(strict_types=1);

namespace App\Module\Cart\Domain\Entity;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\ProductSellingType;
use App\Module\Order\Domain\Support\RentalPeriodCalculator;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cart_items')]
#[ORM\UniqueConstraint(name: 'cart_item_unique_product_period', columns: ['cart_id', 'product_id', 'selling_type', 'rental_months', 'rental_start_date'])]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CartSession $cart;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'selling_type', length: 10)]
    private string $sellingType = ProductSellingType::Sale->value;

    #[ORM\Column(name: 'rental_months', type: 'integer', options: ['default' => -1])]
    private int $rentalMonths = -1;

    #[ORM\Column(name: 'rental_start_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalStartDate = null;

    public function __construct(CartSession $cart, Product $product, ProductSellingType|string|int $sellingTypeOrQuantity, int|\DateTimeImmutable|null $quantityOrRentalMonths = 1, ?int $rentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null)
    {
        $this->cart = $cart;
        $this->product = $product;

        if (is_int($sellingTypeOrQuantity)) {
            if ($product->isAvailableForRental() && !$product->isAvailableForSale()) {
                $resolvedSellingType = ProductSellingType::Rental->value;
                $resolvedQuantity = $sellingTypeOrQuantity;
                $resolvedRentalMonths = is_int($quantityOrRentalMonths) ? $quantityOrRentalMonths : $rentalMonths;
                $resolvedRentalStartDate = $quantityOrRentalMonths instanceof \DateTimeImmutable ? $quantityOrRentalMonths : $rentalStartDate;
            } else {
                $resolvedSellingType = ProductSellingType::Sale->value;
                $resolvedQuantity = $sellingTypeOrQuantity;
                $resolvedRentalMonths = null;
                $resolvedRentalStartDate = null;
            }
        } else {
            $resolvedSellingType = $sellingTypeOrQuantity;
            $resolvedQuantity = is_int($quantityOrRentalMonths) ? $quantityOrRentalMonths : 1;
            $resolvedRentalMonths = $rentalMonths;
            $resolvedRentalStartDate = $rentalStartDate;
        }

        $this->setSellingType($resolvedSellingType);
        $this->setQuantity($resolvedQuantity);
        $this->setRentalMonths($resolvedRentalMonths);
        $this->setRentalStartDate($resolvedRentalStartDate);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): CartSession
    {
        return $this->cart;
    }

    public function setCart(CartSession $cart): self
    {
        $this->cart = $cart;

        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getSellingType(): string
    {
        return $this->sellingType;
    }

    public function setSellingType(ProductSellingType|string $sellingType): self
    {
        $mode = ProductSellingType::fromInput($sellingType);

        if (!$this->product->supportsSellingType($mode)) {
            throw new \InvalidArgumentException('Ce produit ne supporte pas ce mode de commercialisation.');
        }

        $this->sellingType = $mode->value;

        if (ProductSellingType::Sale === $mode) {
            $this->rentalMonths = -1;
            $this->rentalStartDate = null;
        }

        return $this;
    }

    public function setQuantity(int $quantity): self
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantite doit etre superieure ou egale a 1.');
        }

        $this->quantity = $quantity;

        return $this;
    }

    public function increaseQuantity(int $amount = 1): self
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException('L\'augmentation doit etre superieure ou egale a 1.');
        }

        $this->quantity += $amount;

        return $this;
    }

    public function replaceProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getRentalMonths(): ?int
    {
        return $this->rentalMonths > 0 ? $this->rentalMonths : null;
    }

    public function getStoredRentalMonths(): int
    {
        return $this->rentalMonths;
    }

    public function setRentalMonths(?int $rentalMonths): self
    {
        if (ProductSellingType::Sale === $this->sellingType && null !== $rentalMonths) {
            throw new \InvalidArgumentException('La durée de location ne s’applique pas à un achat.');
        }

        if (null === $rentalMonths) {
            $this->rentalMonths = -1;
            $this->rentalStartDate = null;

            return $this;
        }

        if ($rentalMonths < 1) {
            throw new \InvalidArgumentException('La durée de location doit être supérieure ou égale à 1 mois.');
        }

        $this->rentalMonths = $rentalMonths;

        return $this;
    }

    public function getRentalStartDate(): ?\DateTimeImmutable
    {
        return $this->rentalStartDate;
    }

    public function getRentalStartDateString(): ?string
    {
        return RentalPeriodCalculator::formatDate($this->rentalStartDate);
    }

    public function getRentalEndDate(): ?\DateTimeImmutable
    {
        return RentalPeriodCalculator::calculateEndDate($this->rentalStartDate, $this->getRentalMonths());
    }

    public function getRentalEndDateString(): ?string
    {
        return RentalPeriodCalculator::formatDate($this->getRentalEndDate());
    }

    public function setRentalStartDate(?\DateTimeImmutable $rentalStartDate): self
    {
        if (ProductSellingType::Sale === $this->sellingType) {
            $this->rentalStartDate = null;

            return $this;
        }

        if (null === $this->getRentalMonths()) {
            $this->rentalStartDate = null;

            return $this;
        }

        if (null === $rentalStartDate) {
            $this->rentalStartDate = null;

            return $this;
        }

        $this->rentalStartDate = RentalPeriodCalculator::normalizeDate($rentalStartDate);

        return $this;
    }
}
