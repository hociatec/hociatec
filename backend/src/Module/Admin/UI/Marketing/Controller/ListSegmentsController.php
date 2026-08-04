<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Marketing\Application\Service\EmailTemplateScenarioProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/segments', name: 'api_admin_marketing_segments_list', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class ListSegmentsController extends AbstractController
{
    public function __construct(private readonly EmailTemplateScenarioProvider $scenarioProvider)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $type = (string) $request->query->get('type', 'templates');

        $items = match ($type) {
            'campaigns' => $this->scenarioProvider->getCampaignScenarioDefinitions(),
            'transactional' => $this->scenarioProvider->getTransactionalTemplateScenarioDefinitions(),
            default => $this->scenarioProvider->getTemplateScenarioDefinitions(),
        };

        return ApiResponse::success([
            'items' => $items,
        ]);
    }
}
