<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Promotion\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Promotion\Application\Service\PromotionFormatter;
use App\Module\Promotion\Application\Service\UpdatePromotionHandler;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
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
            $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
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
