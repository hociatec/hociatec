<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Service\MarketingCampaignService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns/preview', name: 'api_admin_marketing_campaigns_preview', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class PreviewAudienceController extends AbstractController
{
    public function __construct(private readonly MarketingCampaignService $campaignService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $segmentKey = (string) ($payload['segmentKey'] ?? '');
        $criteria = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [];

        if ('' === $segmentKey) {
            return ApiResponse::error('Veuillez choisir une audience.');
        }

        return ApiResponse::success([
            'preview' => $this->campaignService->previewAudience($segmentKey, $criteria),
            'segments' => $this->campaignService->getSegmentDefinitions(),
        ]);
    }
}
