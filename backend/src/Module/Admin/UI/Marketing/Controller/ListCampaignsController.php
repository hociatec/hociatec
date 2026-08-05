<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Module\Marketing\UI\Http\EmailCampaignResponseFormatter;
use App\Module\Marketing\Application\Port\EmailCampaignRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/campaigns', name: 'api_admin_marketing_campaigns_list', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(
        private readonly EmailCampaignRepositoryPort $campaigns,
        private readonly EmailCampaignResponseFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);

        return ApiResponse::paginated(
            array_map(
                fn ($campaign) => $this->formatter->format($campaign),
                $this->campaigns->findBy([], ['sentAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->campaigns->count([])),
        );
    }
}
