<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\DeleteBetaCampaignHandler;
use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns/{id}', methods: ['DELETE'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class DeleteCampaignController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepositoryPort $campaigns,
        private readonly DeleteBetaCampaignHandler $deleteCampaign,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $campaign = $this->campaigns->find($id);
        if (null === $campaign) {
            return ApiResponse::error('Campagne non trouvée.', 404);
        }

        $this->deleteCampaign->delete($campaign);

        return ApiResponse::success([], 200, 'Campagne supprimée.');
    }
}
