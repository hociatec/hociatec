<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\Repository\PromotionRepository;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/{promotionId}', name: 'api_admin_promotions_get', methods: ['GET'], requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class GetPromotionController extends AbstractController
{
    public function __construct(private readonly PromotionRepository $promotions)
    {
    }

    public function __invoke(int $promotionId): JsonResponse
    {
        $promotion = $this->promotions->find($promotionId);
        if ($promotion === null) {
            return ApiResponse::error('Promotion introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'promotion' => PromotionFormatter::formatPromotion($promotion),
        ]);
    }
}
