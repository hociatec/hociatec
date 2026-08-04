<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Marketing\DTO\MarketingCampaignInput;
use App\Module\Marketing\Application\Service\MarketingCampaignService;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\User\Domain\Entity\User;
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
        private readonly EmailTemplateRepository $templates,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
        $input = MarketingCampaignInput::fromArray($payload);
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

        return ApiResponse::created([
            'campaign' => [
                'id' => $campaign->getId(),
                'name' => $campaign->getName(),
                'recipientsCount' => $campaign->getRecipientsCount(),
                'sentAt' => $campaign->getSentAt()->format(DATE_ATOM),
            ],
        ]);
    }
}
