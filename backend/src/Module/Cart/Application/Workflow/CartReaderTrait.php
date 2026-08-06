<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Workflow;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;

trait CartReaderTrait
{
    public function viewCart(?string $token): CartSession
    {
        return $this->cartSessions->view($token);
    }

    public function findCartByToken(?string $token): ?CartSession
    {
        return $this->cartSessions->findByToken($token);
    }

    private function assertStockAvailability(Product $product, int $requestedQuantity): void
    {
        if ($requestedQuantity > $product->getStock()) {
            throw new \InvalidArgumentException('Stock insuffisant pour ce produit.');
        }
    }
}
