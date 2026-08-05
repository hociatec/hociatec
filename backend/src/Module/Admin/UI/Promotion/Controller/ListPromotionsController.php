<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Promotion\Application\Port\PromotionRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions', name: 'api_admin_promotions_list', methods: ['GET'])]
#[IsGranted('ROLE_PROMOTIONS_MANAGER')]
final class ListPromotionsController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepositoryPort $promotions,
        private readonly PromotionFormatter $formatter,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request);

        return ApiResponse::paginated(
            array_map(
                fn ($promotion) => $this->formatter->formatPromotion($promotion),
                $this->promotions->findBy([], ['updatedAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->promotions->count([])),
        );
    }
}
