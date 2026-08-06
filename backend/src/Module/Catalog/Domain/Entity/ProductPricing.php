<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductPricing
{
    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(length: 10, enumType: ProductSellingType::class, options: ['default' => 'sale'])]
    private ProductSellingType $sellingType = ProductSellingType::Sale;

    public function __construct(int $priceCents)
    {
        $this->changePrice($priceCents);
    }

    public function priceCents(): int
    {
        return $this->priceCents;
    }

    public function changePrice(int $priceCents): void
    {
        $this->priceCents = Money::fromCents($priceCents)->cents();
    }

    public function sellingType(): string
    {
        return $this->sellingType->value;
    }

    public function changeSellingType(ProductSellingType|string $type): void
    {
        $this->sellingType = ProductSellingType::fromInput($type);
    }
}
