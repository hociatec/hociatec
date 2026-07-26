<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\Repository\PromotionRepository;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions', name: 'api_admin_promotions_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListPromotionsController extends AbstractController
{
    public function __construct(private readonly PromotionRepository $promotions)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        return ApiResponse::paginated(
            array_map(
                static fn ($promotion) => PromotionFormatter::formatPromotion($promotion),
                $this->promotions->findBy([], ['updatedAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->promotions->count([])),
        );
    }
}
