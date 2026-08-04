<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;

class ProductReviewStatsUpdater
{
    public function __construct(
        private readonly ProductRatingRepository $ratings,
        private readonly DoctrinePersistence $persistence,
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
