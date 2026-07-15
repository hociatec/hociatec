<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates', name: 'api_admin_marketing_templates_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListTemplatesController extends AbstractController
{
    public function __construct(private readonly EmailTemplateRepository $templates)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(
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
                $this->templates->findBy([], ['updatedAt' => 'DESC']),
            ),
        ]);
    }
}
