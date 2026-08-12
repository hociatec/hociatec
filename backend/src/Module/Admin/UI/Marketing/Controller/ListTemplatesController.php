<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\Application\Provider\EmailTemplateScenarioProvider;
use App\Module\Marketing\Application\Projection\EmailTemplateResponseFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates', name: 'api_admin_marketing_templates_list', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class ListTemplatesController extends AbstractController
{
    public function __construct(
        private readonly EmailTemplateRepositoryPort $templates,
        private readonly EmailTemplateResponseFormatter $formatter,
        private readonly EmailTemplateScenarioProvider $scenarios,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $search = RequestQueryMapper::nullableString($request, 'q');
        $scenario = RequestQueryMapper::nullableString($request, 'scenario');
        $status = RequestQueryMapper::choice($request, 'status', ['active', 'inactive']);
        $usage = RequestQueryMapper::choice($request, 'usage', ['transactional', 'campaign']);
        $scenarioKeys = $this->resolveScenarioKeys($usage);

        $templates = $this->templates->findForAdmin($search, $scenario, $status, 200, 0);
        if (null !== $scenarioKeys) {
            $templates = array_values(
                array_filter(
                    $templates,
                    fn ($template) => in_array($template->getScenarioKey(), $scenarioKeys, true),
                ),
            );
        }

        $total = count($templates);
        $items = array_slice($templates, $pagination->offset(), $pagination->perPage);

        return ApiResponse::paginated(
            array_map(
                fn ($template) => $this->formatter->format($template),
                $items,
            ),
            $pagination->metadata($total),
        );
    }

    /** @return list<string>|null */
    private function resolveScenarioKeys(?string $usage): ?array
    {
        if (null === $usage) {
            return null;
        }

        return array_keys(
            array_filter(
                $this->scenarios->getTemplateScenarioDefinitions(),
                fn (array $definition): bool => ($definition['type'] ?? null) === $usage,
            ),
        );
    }
}
