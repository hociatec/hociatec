<?php

declare(strict_types=1);

namespace App\Module\Rating\Service;

use App\Module\Catalog\Entity\Product;
use App\Module\Rating\Repository\ProductRatingRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductReviewStatsUpdater
{
    public function __construct(
        private readonly ProductRatingRepository $ratings,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function refresh(Product $product): void
    {
        $stats = $this->ratings->getStatsForProduct($product);
        $product->setReviewsCount($stats['count']);
        $product->setReviewsAverage($stats['average']);
        $this->em->persist($product);
        $this->em->flush();
    }
}
