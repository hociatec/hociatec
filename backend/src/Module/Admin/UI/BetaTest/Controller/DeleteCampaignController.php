<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\BetaTest\Service\DeleteBetaCampaignHandler;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns/{id}', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteCampaignController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepository $campaigns,
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
