<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Provider;

use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\ProductSellingType;
use App\Module\Order\Application\Support\RentalPeriodCalculator;

final readonly class CartItemResolver
{
    public function normalizeSellingType(Product $product, ?string $sellingType = null, ?CartItem $existingItem = null): string
    {
        if (null !== $sellingType) {
            $mode = ProductSellingType::fromInput($sellingType);
        } elseif ($existingItem instanceof CartItem) {
            $mode = ProductSellingType::fromInput($existingItem->getSellingType());
        } elseif ($product->isAvailableForSale() && !$product->isAvailableForRental()) {
            $mode = ProductSellingType::Sale;
        } elseif ($product->isAvailableForRental() && !$product->isAvailableForSale()) {
            $mode = ProductSellingType::Rental;
        } else {
            throw new \InvalidArgumentException('Le mode de commercialisation est requis pour ce produit.');
        }

        if (!$product->supportsSellingType($mode)) {
            throw new \InvalidArgumentException('Ce produit ne supporte pas ce mode de commercialisation.');
        }

        return $mode->value;
    }

    public function determineRentalMonths(Product $product, mixed $sellingTypeOrRequestedMonths = null, mixed $requestedMonthsOrExistingItem = null, ?CartItem $existingItem = null): ?int
    {
        if (null === $existingItem && $requestedMonthsOrExistingItem instanceof CartItem) {
            $existingItem = $requestedMonthsOrExistingItem;
            $requestedMonthsOrExistingItem = null;
        }

        if (null === $requestedMonthsOrExistingItem && (null === $sellingTypeOrRequestedMonths || is_int($sellingTypeOrRequestedMonths))) {
            $sellingType = $product->isAvailableForRental() && !$product->isAvailableForSale() ? 'rental' : 'sale';
            $requestedMonths = is_int($sellingTypeOrRequestedMonths) ? $sellingTypeOrRequestedMonths : null;
        } else {
            $sellingType = $sellingTypeOrRequestedMonths;
            $requestedMonths = $requestedMonthsOrExistingItem;
        }

        if ('rental' !== $sellingType) {
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

    public function determineRentalStartDate(Product $product, string $sellingType, ?\DateTimeImmutable $requestedStartDate, ?CartItem $existingItem = null): ?\DateTimeImmutable
    {
        if ('rental' !== $sellingType) {
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

    public function resolveExistingItem(CartSession $cart, Product $product, mixed $sellingTypeOrRentalMonths = null, ?int $rentalMonths = null, ?\DateTimeImmutable $rentalStartDate = null): ?CartItem
    {
        if (null === $rentalMonths && (null === $sellingTypeOrRentalMonths || is_int($sellingTypeOrRentalMonths))) {
            $sellingType = $product->isAvailableForRental() && !$product->isAvailableForSale() ? 'rental' : 'sale';
            $rentalMonths = is_int($sellingTypeOrRentalMonths) ? $sellingTypeOrRentalMonths : null;
        } else {
            $sellingType = $sellingTypeOrRentalMonths;
        }

        if ('rental' !== $sellingType) {
            return $cart->getItemForProduct($product, $sellingType);
        }

        if (null !== $rentalMonths) {
            return $cart->getItemForProduct($product, $sellingType, $rentalMonths, RentalPeriodCalculator::normalizeDate($rentalStartDate));
        }

        $items = $cart->getItemsForProduct($product, $sellingType);

        if (\count($items) > 1) {
            throw new \InvalidArgumentException('Plusieurs durées de location existent pour ce produit. Précisez "currentRentalMonths".');
        }

        return $items[0] ?? null;
    }

    public function getTotalQuantityForProduct(CartSession $cart, Product $product, mixed $sellingType = null, ?CartItem $exclude = null): int
    {
        if ($sellingType instanceof CartItem) {
            $exclude = $sellingType;
            $sellingType = null;
        }

        $total = 0;

        foreach ($cart->getItemsForProduct($product, $sellingType) as $item) {
            if (null !== $exclude && $item === $exclude) {
                continue;
            }

            $total += $item->getQuantity();
        }

        return $total;
    }
}
