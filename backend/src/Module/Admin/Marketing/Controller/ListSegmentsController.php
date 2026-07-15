<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Service\MarketingCampaignService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/segments', name: 'api_admin_marketing_segments_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListSegmentsController extends AbstractController
{
    public function __construct(private readonly MarketingCampaignService $campaignService)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => $this->campaignService->getSegmentDefinitions(),
        ]);
    }
}
