<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\Service\PromotionEngine;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/audiences', name: 'api_admin_promotions_audiences', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListPromotionAudiencesController extends AbstractController
{
    public function __construct(private readonly PromotionEngine $promotionEngine)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => $this->promotionEngine->getAudienceDefinitions(),
        ]);
    }
}
