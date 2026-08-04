<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\ApiValidationException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\BetaTest\DTO\CreateBetaCampaignInput;
use App\Module\Admin\Application\BetaTest\Service\CreateBetaCampaignHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns', methods: ['POST'])] #[IsGranted('ROLE_ADMIN')]
final class CreateCampaignController extends AbstractController
{
    public function __construct(
        private readonly CreateBetaCampaignHandler $createCampaign,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = \App\Infrastructure\Http\JsonRequestInput::decode($request, CreateBetaCampaignInput::class);
            $this->validator->validate($input);
            $campaign = $this->createCampaign->create($input);
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode, $exception->details);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::createdItem('id', $campaign->getId(), 'Campagne créée.');
    }
}
