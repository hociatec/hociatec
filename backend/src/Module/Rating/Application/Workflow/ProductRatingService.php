<?php

declare(strict_types=1);

namespace App\Module\Rating\Application\Workflow;

use App\Module\Comment\Domain\Entity\ProductComment;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Application\Exception\ProductReviewException;
use App\Module\Rating\Application\Persistence\RatingPersistence;
use App\Module\Rating\Application\Writer\ProductReviewStatsUpdater;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\Rating\Infrastructure\Repository\ProductRatingRepository;
use App\Module\User\Domain\Entity\User;

class ProductRatingService
{
    public function __construct(
        private readonly ProductRatingRepository $ratings,
        private readonly ProductReviewStatsUpdater $statsUpdater,
        private readonly RatingPersistence $persistence,
    ) {
    }

    public function createReview(User $user, Order $order, OrderItem $item, int $score, ?string $comment): ProductRating
    {
        if ($order->getUser()->getId() !== $user->getId()) {
            throw new ProductReviewException('Commande introuvable.');
        }

        if (Order::STATUS_DELIVERED !== $order->getStatus()) {
            throw new ProductReviewException('Vous pourrez laisser un avis une fois la commande livrée.');
        }

        $product = $item->getProduct();
        if (null === $product) {
            throw new ProductReviewException('Produit introuvable.');
        }

        if ($this->ratings->existsForOrderItem($item)) {
            throw new ProductReviewException('Vous avez déjà noté ce produit.');
        }

        if ($score < 1 || $score > 5) {
            throw new ProductReviewException('Note invalide.');
        }

        $rating = new ProductRating($product, $item, $user, $score);
        $rating->publish();

        $body = trim((string) $comment);
        if ('' !== $body) {
            $productComment = new ProductComment($rating, $body);
            $rating->setComment($productComment);
            $this->persistence->persist($productComment);
        }

        $this->persistence->persist($rating);
        $this->persistence->commit();

        $this->statsUpdater->refresh($product);

        return $rating;
    }
}
