<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns', name: 'api_admin_marketing_campaigns_list', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(private readonly EmailCampaignRepository $campaigns)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);

        return ApiResponse::paginated(
            array_map(
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
                        'id' => $campaign->getTemplate()->getId(),
                        'name' => $campaign->getTemplate()->getName(),
                    ] : null,
                ],
                $this->campaigns->findBy([], ['sentAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->campaigns->count([])),
        );
    }
}
