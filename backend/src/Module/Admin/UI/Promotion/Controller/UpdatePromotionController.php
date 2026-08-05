<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Application\Handler\UpdatePromotionHandler;
use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Promotion\Application\Port\PromotionRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions/{promotionId}', name: 'api_admin_promotions_update', methods: ['PUT'], requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_PROMOTIONS_MANAGER')]
final class UpdatePromotionController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepositoryPort $promotions,
        private readonly UpdatePromotionHandler $updatePromotion,
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
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PromotionInput::fromArray($payload);
        $this->validator->validate($input);
        $this->updatePromotion->update($promotion, $input);

        return ApiResponse::success([
            'promotion' => PromotionFormatter::formatPromotion($promotion),
        ], JsonResponse::HTTP_OK, 'La promotion a bien été mise à jour.');
    }
}
