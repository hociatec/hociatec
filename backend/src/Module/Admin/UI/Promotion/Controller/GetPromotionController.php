<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Module\Promotion\Application\Port\PromotionRepositoryPort;
use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/{promotionId}', name: 'api_admin_promotions_get', methods: ['GET'], requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_PROMOTIONS_MANAGER')]
final class GetPromotionController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepositoryPort $promotions,
        private readonly PromotionFormatter $formatter,
    ) {
    }

    public function __invoke(int $promotionId): JsonResponse
    {
        $promotion = $this->promotions->find($promotionId);
        if (null === $promotion) {
            return ApiResponse::error('Promotion introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'promotion' => $this->formatter->formatPromotion($promotion),
        ]);
    }
}
