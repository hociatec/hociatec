<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\DTO\UpdateBetaCampaignInput;
use App\Module\Admin\Application\BetaTest\Handler\UpdateBetaCampaignHandler;
use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\ApiValidationException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns/{id}', methods: ['PATCH', 'PUT'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class UpdateCampaignController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepositoryPort $campaigns,
        private readonly UpdateBetaCampaignHandler $updateCampaign,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $campaign = $this->campaigns->find($id);
        if (null === $campaign) {
            return ApiResponse::error('Campagne non trouvée.', 404);
        }

        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, UpdateBetaCampaignInput::class);
            $this->validator->validate($input);
            $this->updateCampaign->update($campaign, $input);
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode, $exception->details);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(['id' => $campaign->getId()], 200, 'Campagne mise à jour.');
    }
}
