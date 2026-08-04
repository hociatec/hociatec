<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductPricing
{
    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(length: 10, options: ['default' => 'sale'])]
    private string $sellingType = 'sale';

    public function __construct(int $priceCents)
    {
        $this->priceCents = $priceCents;
    }

    public function priceCents(): int
    {
        return $this->priceCents;
    }

    public function changePrice(int $priceCents): void
    {
        $this->priceCents = $priceCents;
    }

    public function sellingType(): string
    {
        return $this->sellingType;
    }

    public function changeSellingType(string $type): void
    {
        $type = strtolower($type);
        if (!in_array($type, ['sale', 'rental'], true)) {
            throw new \InvalidArgumentException('Type de vente/location invalide.');
        }

        $this->sellingType = $type;
    }
}
