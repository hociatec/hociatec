<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductInventory
{
    #[ORM\Column(type: 'integer')]
    private int $stock;

    #[ORM\Column(type: 'integer', options: ['default' => 3])]
    private int $lowStockThreshold = 3;

    public function __construct(int $stock)
    {
        $this->stock = $stock;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function changeStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function lowStockThreshold(): int
    {
        return $this->lowStockThreshold;
    }

    public function changeLowStockThreshold(int $threshold): void
    {
        if ($threshold < 0) {
            throw new \InvalidArgumentException('Le seuil de stock ne peut pas être négatif.');
        }

        $this->lowStockThreshold = $threshold;
    }
}
