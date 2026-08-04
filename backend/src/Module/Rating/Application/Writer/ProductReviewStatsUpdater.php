<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Writer;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

class ProductReviewStatsUpdater
{
    public function __construct(
        private readonly ProductRatingRepository $ratings,
        private readonly DoctrineUnitOfWork $persistence,
    ) {
    }

    public function refresh(Product $product): void
    {
        $stats = $this->ratings->getStatsForProduct($product);
        $product->setReviewsCount($stats['count']);
        $product->setReviewsAverage($stats['average']);
        $this->persistence->persist($product);
        $this->persistence->commit();
    }
}
