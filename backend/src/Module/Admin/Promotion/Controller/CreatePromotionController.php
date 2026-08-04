<?php

declare(strict_types=1);

namespace App\Module\Admin\Promotion\Controller;

use App\Module\Promotion\DTO\PromotionInput;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Module\Promotion\Service\PromotionManager;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\InvalidJsonPayloadException;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/promotions', name: 'api_admin_promotions_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreatePromotionController extends AbstractController
{
    public function __construct(
        private readonly PromotionManager $promotions,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PromotionInput::fromArray($payload);
        $this->validator->validate($input);
        $promotion = $this->promotions->create($input);

        return ApiResponse::created([
            'promotion' => PromotionFormatter::formatPromotion($promotion),
        ], 'La promotion a bien été créée.');
    }
}
