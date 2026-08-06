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
        $this->changeStock($stock);
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function changeStock(int $stock): void
    {
        if ($stock < 0) {
            throw new \InvalidArgumentException('Le stock ne peut pas etre negatif.');
        }

        $this->stock = $stock;
    }

    public function increase(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);
        $this->stock += $quantity;
    }

    public function decrease(int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);
        $this->changeStock($this->stock - $quantity);
    }

    public function reserve(int $quantity): void
    {
        $this->decrease($quantity);
    }

    public function release(int $quantity): void
    {
        $this->increase($quantity);
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

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La quantite de stock doit etre positive.');
        }
    }
}
