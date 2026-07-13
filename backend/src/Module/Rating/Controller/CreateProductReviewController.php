<?php

declare(strict_types=1);

namespace App\Module\Rating\Controller;

use App\Module\Order\Repository\OrderRepository;
use App\Module\Rating\Exception\ProductReviewException;
use App\Module\Rating\Service\ProductRatingService;
use App\Module\Rating\Service\ProductReviewFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/items/{itemId}/review', name: 'api_orders_item_review', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateProductReviewController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ProductRatingService $reviews,
    ) {
    }

    public function __invoke(int $orderId, int $itemId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($order->getUser()->getId() !== $user->getId()) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $targetItem = null;
        foreach ($order->getItems() as $item) {
            if ($item->getId() === $itemId) {
                $targetItem = $item;
                break;
            }
        }

        if ($targetItem === null) {
            return ApiResponse::error('Article introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent() ?: '[]', true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $score = isset($payload['score']) ? (int) $payload['score'] : 0;
        $comment = isset($payload['comment']) ? (string) $payload['comment'] : null;

        try {
            $rating = $this->reviews->createReview($user, $order, $targetItem, $score, $comment);
        } catch (ProductReviewException $e) {
            return ApiResponse::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'review' => ProductReviewFormatter::formatRating($rating, true),
        ]);
    }
}
