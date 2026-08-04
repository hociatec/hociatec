<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Promotion\Application\Service\PromotionFormatter;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
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
