<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Port;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;

interface ProductRatingRepositoryPort
{
    /**
     * @param list<int> $orderItemIds
     *
     * @return array<int, ProductRating>
     */
    public function findByOrderItemIds(array $orderItemIds): array;

    /** @return list<ProductRating> */
    public function findPublishedByProduct(Product $product, int $limit, int $offset = 0): array;

    /** @return array{count:int,average:float} */
    public function getStatsForProduct(Product $product): array;

    public function existsForOrderItem(OrderItem $orderItem): bool;
}
