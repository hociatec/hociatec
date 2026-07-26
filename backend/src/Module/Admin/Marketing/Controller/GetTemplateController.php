<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates/{templateId}', name: 'api_admin_marketing_templates_get', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class GetTemplateController extends AbstractController
{
    public function __construct(private readonly EmailTemplateRepository $templates)
    {
    }

    public function __invoke(int $templateId): JsonResponse
    {
        $template = $this->templates->find($templateId);
        if (null === $template) {
            return ApiResponse::error('Template introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'template' => [
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
        ]);
    }
}
