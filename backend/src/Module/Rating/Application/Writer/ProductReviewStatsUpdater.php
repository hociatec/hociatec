<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Writer;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Rating\Application\Port\ProductRatingRepositoryPort;
use App\Shared\Application\UnitOfWork;

class ProductReviewStatsUpdater
{
    public function __construct(
        private readonly ProductRatingRepositoryPort $ratings,
        private readonly UnitOfWork $persistence,
    ) {
    }

    public function refresh(Product $product): void
    {
        $stats = $this->ratings->getStatsForProduct($product);
        $product->setReviewsCount($stats['count']);
        $product->setReviewsAverage($stats['average']);
        $this->persistence->persist($product);
        $this->persistence->flush();
    }
}
