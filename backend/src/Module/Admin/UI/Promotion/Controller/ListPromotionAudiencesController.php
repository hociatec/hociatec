<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/audiences', name: 'api_admin_promotions_audiences', methods: ['GET'])]
#[IsGranted('ROLE_PROMOTIONS_MANAGER')]
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
