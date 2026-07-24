<?php

declare(strict_types=1);

namespace App\Module\Rating\Service;

use App\Module\Comment\Entity\ProductComment;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Rating\Entity\ProductRating;
use App\Module\Rating\Exception\ProductReviewException;
use App\Module\Rating\Repository\ProductRatingRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ProductRatingService
{
    public function __construct(
        private readonly ProductRatingRepository $ratings,
        private readonly ProductReviewStatsUpdater $statsUpdater,
        private readonly EntityManagerInterface $em,
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
            $this->em->persist($productComment);
        }

        $this->em->persist($rating);
        $this->em->flush();

        $this->statsUpdater->refresh($product);

        return $rating;
    }
}
