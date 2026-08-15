<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductPricing
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $salePriceCents = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalPriceCents = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $availableForSale = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $availableForRental = false;

    public function __construct(?int $salePriceCents = null, ?int $rentalPriceCents = null, bool $availableForSale = true, bool $availableForRental = false)
    {
        $this->changeSalePrice($salePriceCents);
        $this->changeRentalPrice($rentalPriceCents);
        $this->changeAvailability($availableForSale, $availableForRental);
    }

    public function salePriceCents(): ?int
    {
        return $this->salePriceCents;
    }

    public function changeSalePrice(?int $priceCents): void
    {
        $this->salePriceCents = null !== $priceCents ? Money::fromCents($priceCents)->cents() : null;
    }

    public function rentalPriceCents(): ?int
    {
        return $this->rentalPriceCents;
    }

    public function changeRentalPrice(?int $priceCents): void
    {
        $this->rentalPriceCents = null !== $priceCents ? Money::fromCents($priceCents)->cents() : null;
    }

    public function availableForSale(): bool
    {
        return $this->availableForSale;
    }

    public function availableForRental(): bool
    {
        return $this->availableForRental;
    }

    public function supports(ProductSellingType|string $type): bool
    {
        $mode = ProductSellingType::fromInput($type);

        return match ($mode) {
            ProductSellingType::Sale => $this->availableForSale,
            ProductSellingType::Rental => $this->availableForRental,
        };
    }

    public function changeAvailability(bool $availableForSale, bool $availableForRental): void
    {
        if (!$availableForSale && !$availableForRental) {
            throw new \InvalidArgumentException('Le produit doit être disponible à la vente, à la location ou aux deux.');
        }

        if ($availableForSale && null === $this->salePriceCents) {
            throw new \InvalidArgumentException('Le prix de vente est obligatoire lorsque le produit est disponible à la vente.');
        }

        if ($availableForRental && null === $this->rentalPriceCents) {
            throw new \InvalidArgumentException('Le prix mensuel est obligatoire lorsque le produit est disponible à la location.');
        }

        if (!$availableForSale) {
            $this->salePriceCents = null;
        }

        if (!$availableForRental) {
            $this->rentalPriceCents = null;
        }

        $this->availableForSale = $availableForSale;
        $this->availableForRental = $availableForRental;
    }

    public function unitPriceFor(ProductSellingType|string $type): int
    {
        $mode = ProductSellingType::fromInput($type);

        return match ($mode) {
            ProductSellingType::Sale => $this->salePriceCents ?? throw new \InvalidArgumentException('Prix de vente indisponible.'),
            ProductSellingType::Rental => $this->rentalPriceCents ?? throw new \InvalidArgumentException('Prix de location indisponible.'),
        };
    }
}
