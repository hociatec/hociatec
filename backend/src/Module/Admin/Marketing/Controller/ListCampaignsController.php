<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Repository\EmailCampaignRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns', name: 'api_admin_marketing_campaigns_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(private readonly EmailCampaignRepository $campaigns)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(
                static fn ($campaign) => [
                    'id' => $campaign->getId(),
                    'name' => $campaign->getName(),
                    'segmentKey' => $campaign->getSegmentKey(),
                    'criteria' => $campaign->getCriteria(),
                    'subjectSnapshot' => $campaign->getSubjectSnapshot(),
                    'recipientsCount' => $campaign->getRecipientsCount(),
                    'createdByEmail' => $campaign->getCreatedByEmail(),
                    'sentAt' => $campaign->getSentAt()->format(DATE_ATOM),
                    'template' => $campaign->getTemplate() ? [
                        'id' => $campaign->getTemplate()?->getId(),
                        'name' => $campaign->getTemplate()?->getName(),
                    ] : null,
                ],
                $this->campaigns->findBy([], ['sentAt' => 'DESC']),
            ),
        ]);
    }
}
