<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Entity\EmailTemplate;
use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Marketing\Service\EmailTemplateScenarioProvider;
use App\Module\Admin\Marketing\Service\EmailTemplateAdminManager;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates', name: 'api_admin_marketing_templates_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailTemplateAdminManager $manager,
        private readonly EmailTemplateRepository $templates,
        private readonly EmailTemplateScenarioProvider $scenarioProvider,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $name = trim((string) ($payload['name'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));
        $scenarioKey = trim((string) ($payload['scenarioKey'] ?? ''));
        $subjectTemplate = trim((string) ($payload['subjectTemplate'] ?? ''));
        $htmlBody = trim((string) ($payload['htmlBody'] ?? ''));
        $textBody = isset($payload['textBody']) ? trim((string) $payload['textBody']) : null;
        $isActive = (bool) ($payload['isActive'] ?? true);

        if ('' === $name || '' === $slug || '' === $scenarioKey || '' === $subjectTemplate || '' === $htmlBody) {
            return ApiResponse::error('Veuillez renseigner tous les champs obligatoires.');
        }

        if (!isset($this->scenarioProvider->getTemplateScenarioDefinitions()[$scenarioKey])) {
            return ApiResponse::error('Scénario de template invalide.');
        }

        if (null !== $this->templates->findOneBySlug($slug)) {
            return ApiResponse::error('Ce slug de template est déjà utilisé.');
        }

        $template = new EmailTemplate($name, $slug, $scenarioKey, $subjectTemplate, $htmlBody, $textBody);
        $template->setIsActive($isActive);

        $this->manager->create($template);

        return ApiResponse::created([
            'template' => [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'slug' => $template->getSlug(),
                'scenarioKey' => $template->getScenarioKey(),
                'subjectTemplate' => $template->getSubjectTemplate(),
                'htmlBody' => $template->getHtmlBody(),
                'textBody' => $template->getTextBody(),
                'isActive' => $template->isActive(),
            ],
        ]);
    }
}
