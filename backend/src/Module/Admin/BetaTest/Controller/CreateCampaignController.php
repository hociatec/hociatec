<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\Admin\BetaTest\Service\AdminBetaCampaignManager;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns', methods: ['POST'])] #[IsGranted('ROLE_ADMIN')]
final class CreateCampaignController extends AbstractController
{
    public function __construct(private readonly AdminBetaCampaignManager $campaignManager)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $campaign = $this->campaignManager->create(JsonPayload::decode($request));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::created(['id' => $campaign->getId()], 'Campagne créée.');
    }
}
