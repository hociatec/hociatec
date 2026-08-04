<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Service;

use App\Module\Catalog\Domain\Entity\Product;

final class ProductDiscountApplicator
{
    public function applyOnCreate(
        Product $product,
        ?bool $enabled,
        ?string $type,
        ?int $value,
        ?\DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt,
    ): void {
        if (null !== $enabled) {
            $product->setDiscountEnabled($enabled);
        }
        if (null !== $type) {
            $product->setDiscountType($type);
        }
        if (null !== $value) {
            $product->setDiscountValue($value);
        }
        if (null !== $startsAt) {
            $product->setDiscountStartsAt($startsAt);
        }
        if (null !== $endsAt) {
            $product->setDiscountEndsAt($endsAt);
        }
    }

    public function applyOnUpdate(
        Product $product,
        ?bool $enabled,
        ?string $type,
        ?int $value,
        ?\DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt,
    ): void {
        if (null !== $enabled) {
            $product->setDiscountEnabled($enabled);
        }
        if (null !== $type) {
            $product->setDiscountType($type);
        }
        if (null !== $value) {
            $product->setDiscountValue($value);
        }
        if (null !== $startsAt || null !== $endsAt) {
            $product->setDiscountStartsAt($startsAt);
            $product->setDiscountEndsAt($endsAt);
        }
    }
}
