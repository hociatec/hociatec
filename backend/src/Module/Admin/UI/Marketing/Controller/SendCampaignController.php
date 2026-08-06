<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Module\Admin\UI\Marketing\Http\MarketingRequestMapper;
use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\Application\Workflow\MarketingCampaignService;
use App\Module\Marketing\UI\Http\EmailCampaignResponseFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns/send', name: 'api_admin_marketing_campaigns_send', methods: ['POST'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class SendCampaignController extends AbstractController
{
    public function __construct(
        private readonly MarketingCampaignService $campaignService,
        private readonly EmailTemplateRepositoryPort $templates,
        private readonly DtoValidator $validator,
        private readonly MarketingRequestMapper $requests,
        private readonly EmailCampaignResponseFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $input = $this->requests->campaign($request);
        $this->validator->validate($input);

        $template = $input->templateId ? $this->templates->find($input->templateId) : null;

        /** @var User|null $actor */
        $actor = $this->getUser();

        $campaign = $this->campaignService->sendCampaign(
            $input->name, $input->segmentKey, $input->criteria, $input->subject,
            $input->htmlBody, $input->textBody,
            $template,
            $actor?->getEmail(),
        );

        return ApiResponse::createdItem('campaign', $this->formatter->summary($campaign));
    }
}
