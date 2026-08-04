<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates', name: 'api_admin_marketing_templates_list', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class ListTemplatesController extends AbstractController
{
    public function __construct(private readonly EmailTemplateRepository $templates)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);

        return ApiResponse::paginated(
            array_map(
                static fn ($template) => [
                    'id' => $template->getId(),
                    'name' => $template->getName(),
                    'slug' => $template->getSlug(),
                    'scenarioKey' => $template->getScenarioKey(),
                    'subjectTemplate' => $template->getSubjectTemplate(),
                    'htmlBody' => $template->getHtmlBody(),
                    'textBody' => $template->getTextBody(),
                    'isActive' => $template->isActive(),
                    'createdAt' => $template->getCreatedAt()->format(DATE_ATOM),
                    'updatedAt' => $template->getUpdatedAt()->format(DATE_ATOM),
                ],
                $this->templates->findBy([], ['updatedAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->templates->count([])),
        );
    }
}
