<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\UI\Http\EmailTemplateResponseFormatter;
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
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);

        return ApiResponse::paginated(
            array_map(
                fn ($template) => $this->formatter->format($template),
                $this->templates->findBy([], ['updatedAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->templates->count([])),
        );
    }
}
