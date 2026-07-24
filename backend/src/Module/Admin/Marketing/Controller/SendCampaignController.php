<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Marketing\Service\MarketingCampaignService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns/send', name: 'api_admin_marketing_campaigns_send', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class SendCampaignController extends AbstractController
{
    public function __construct(
        private readonly MarketingCampaignService $campaignService,
        private readonly EmailTemplateRepository $templates,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $name = trim((string) ($payload['name'] ?? ''));
        $segmentKey = trim((string) ($payload['segmentKey'] ?? ''));
        $criteria = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [];
        $subject = trim((string) ($payload['subject'] ?? ''));
        $htmlBody = trim((string) ($payload['htmlBody'] ?? ''));
        $textBody = isset($payload['textBody']) ? trim((string) $payload['textBody']) : null;
        $templateId = isset($payload['templateId']) ? (int) $payload['templateId'] : null;

        if ('' === $name || '' === $segmentKey || '' === $subject || '' === $htmlBody) {
            return ApiResponse::error('Veuillez renseigner le nom, l’audience, l’objet et le contenu HTML.');
        }

        $template = $templateId ? $this->templates->find($templateId) : null;

        /** @var User|null $actor */
        $actor = $this->getUser();

        $campaign = $this->campaignService->sendCampaign(
            $name,
            $segmentKey,
            $criteria,
            $subject,
            $htmlBody,
            $textBody,
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
