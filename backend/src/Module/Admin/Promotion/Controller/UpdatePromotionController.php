<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\DTO\PromotionInput;
use App\Module\Promotion\Repository\PromotionRepository;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Module\Promotion\Service\PromotionManager;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/{promotionId}', name: 'api_admin_promotions_update', methods: ['PUT'], requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdatePromotionController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepository $promotions,
        private readonly PromotionManager $manager,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $promotionId, Request $request): JsonResponse
    {
        $promotion = $this->promotions->find($promotionId);
        if (null === $promotion) {
            return ApiResponse::error('Promotion introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PromotionInput::fromArray($payload);
        $this->validator->validate($input);
        $this->manager->update($promotion, $input);

        return ApiResponse::success([
            'promotion' => PromotionFormatter::formatPromotion($promotion),
        ]);
    }
}
