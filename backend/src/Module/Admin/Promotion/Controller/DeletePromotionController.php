<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\Repository\PromotionRepository;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $promotionId): JsonResponse
    {
        $promotion = $this->promotions->find($promotionId);
        if ($promotion === null) {
            return ApiResponse::error('Promotion introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($promotion);
        $this->entityManager->flush();

        return ApiResponse::success(['deleted' => true]);
    }
}
