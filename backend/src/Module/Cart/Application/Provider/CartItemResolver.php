<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Provider;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Application\Support\RentalPeriodCalculator;

final readonly class CartItemResolver
{
    public function determineRentalMonths(Product $product, ?int $requestedMonths, ?CartItem $existingItem = null): ?int
    {
        if ('rental' !== $product->getSellingType()) {
            return null;
        }

        if (null === $requestedMonths) {
            $existingMonths = $existingItem?->getRentalMonths();

            if (null === $existingMonths) {
                throw new \InvalidArgumentException('Champ "rentalMonths" requis pour ce produit.');
            }

            return $existingMonths;
        }

        if ($requestedMonths < 1) {
            throw new \InvalidArgumentException('La durée de location doit être supérieure ou égale à 1 mois.');
        }

        return $requestedMonths;
    }

    public function determineRentalStartDate(Product $product, ?\DateTimeImmutable $requestedStartDate, ?CartItem $existingItem = null): ?\DateTimeImmutable
    {
        if ('rental' !== $product->getSellingType()) {
            return null;
        }

        $requestedStartDate = RentalPeriodCalculator::normalizeDate($requestedStartDate);
        if (null === $requestedStartDate) {
            $existingStartDate = $existingItem?->getRentalStartDate();
            if (null === $existingStartDate) {
                throw new \InvalidArgumentException('Champ "rentalStartDate" requis pour ce produit.');
            }

            return $existingStartDate;
        }

        $today = new \DateTimeImmutable('today');
        if ($requestedStartDate < $today) {
            throw new \InvalidArgumentException('La date de debut de location doit etre aujourd\'hui ou dans le futur.');
        }

        return $requestedStartDate;
    }

    public function resolveExistingItem(CartSession $cart, Product $product, ?int $rentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null): ?CartItem
    {
        if ('rental' !== $product->getSellingType()) {
            return $cart->getItemForProduct($product);
        }

        if (null !== $rentalMonths) {
            return $cart->getItemForProduct($product, $rentalMonths, RentalPeriodCalculator::normalizeDate($rentalStartDate));
        }

        $items = $cart->getItemsForProduct($product);

        if (\count($items) > 1) {
            throw new \InvalidArgumentException('Plusieurs durées de location existent pour ce produit. Précisez "currentRentalMonths".');
        }

        return $items[0] ?? null;
    }

    public function getTotalQuantityForProduct(CartSession $cart, Product $product, ?CartItem $exclude = null): int
    {
        $total = 0;

        foreach ($cart->getItemsForProduct($product) as $item) {
            if (null !== $exclude && $item === $exclude) {
                continue;
            }

            $total += $item->getQuantity();
        }

        return $total;
    }
}
