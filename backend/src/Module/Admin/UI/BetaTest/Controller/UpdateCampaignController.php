<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Module\Admin\Application\BetaTest\Service\UpdateBetaCampaignHandler;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns/{id}', methods: ['PATCH', 'PUT'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateCampaignController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepository $campaigns,
        private readonly UpdateBetaCampaignHandler $updateCampaign,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $campaign = $this->campaigns->find($id);
        if (null === $campaign) {
            return ApiResponse::error('Campagne non trouvée.', 404);
        }

        try {
            $this->updateCampaign->update($campaign, JsonPayload::decode($request));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(['id' => $campaign->getId()], 200, 'Campagne mise à jour.');
    }
}
