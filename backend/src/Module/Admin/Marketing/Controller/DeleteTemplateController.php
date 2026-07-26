<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Admin\Marketing\Service\EmailTemplateAdminManager;
use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates/{templateId}', name: 'api_admin_marketing_templates_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class DeleteTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailTemplateAdminManager $manager,
        private readonly EmailTemplateRepository $templates,
    ) {
    }

    public function __invoke(int $templateId): JsonResponse
    {
        $template = $this->templates->find($templateId);
        if (null === $template) {
            return ApiResponse::error('Template introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->manager->delete($template);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'Le modèle d’e-mail a bien été supprimé.');
    }
}
