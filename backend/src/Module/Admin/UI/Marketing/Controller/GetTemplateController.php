<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Module\Marketing\Infrastructure\Http\EmailTemplateResponseFormatter;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates/{templateId}', name: 'api_admin_marketing_templates_get', methods: ['GET'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class GetTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailTemplateRepository $templates,
        private readonly EmailTemplateResponseFormatter $formatter,
    ) {
    }

    public function __invoke(int $templateId): JsonResponse
    {
        $template = $this->templates->find($templateId);
        if (null === $template) {
            return ApiResponse::error('Template introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::successItem('template', $this->formatter->format($template));
    }
}
