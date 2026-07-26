<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Admin\Marketing\DTO\MarketingAudienceInput;
use App\Module\Marketing\Service\MarketingCampaignService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns/preview', name: 'api_admin_marketing_campaigns_preview', methods: ['POST'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class PreviewAudienceController extends AbstractController
{
    public function __construct(
        private readonly MarketingCampaignService $campaignService,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = MarketingAudienceInput::fromArray($payload);
        $this->validator->validate($input);

        return ApiResponse::success([
            'preview' => $this->campaignService->previewAudience($input->segmentKey, $input->criteria),
            'segments' => $this->campaignService->getSegmentDefinitions(),
        ]);
    }
}
