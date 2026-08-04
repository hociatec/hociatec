<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Promotion\Application\Service\DeletePromotionHandler;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/{promotionId}', name: 'api_admin_promotions_delete', methods: ['DELETE'], requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class DeletePromotionController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepository $promotions,
        private readonly DeletePromotionHandler $deletePromotion,
    ) {
    }

    public function __invoke(int $promotionId): JsonResponse
    {
        $promotion = $this->promotions->find($promotionId);
        if (null === $promotion) {
            return ApiResponse::error('Promotion introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->deletePromotion->delete($promotion);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'La promotion a bien été supprimée.');
    }
}
