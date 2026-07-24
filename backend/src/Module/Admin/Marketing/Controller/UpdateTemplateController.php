<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Marketing\Service\EmailTemplateScenarioProvider;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates/{templateId}', name: 'api_admin_marketing_templates_update', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateTemplateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailTemplateRepository $templates,
        private readonly EmailTemplateScenarioProvider $scenarioProvider,
    ) {
    }

    public function __invoke(int $templateId, Request $request): JsonResponse
    {
        $template = $this->templates->find($templateId);
        if (null === $template) {
            return ApiResponse::error('Template introuvable.', Response::HTTP_NOT_FOUND);
        }

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

        $existing = $this->templates->findOneBySlug($slug);
        if (null !== $existing && $existing->getId() !== $template->getId()) {
            return ApiResponse::error('Ce slug de template est déjà utilisé.');
        }

        $template
            ->setName($name)
            ->setSlug($slug)
            ->setScenarioKey($scenarioKey)
            ->setSubjectTemplate($subjectTemplate)
            ->setHtmlBody($htmlBody)
            ->setTextBody($textBody)
            ->setIsActive($isActive);

        $this->entityManager->flush();

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
            ],
        ]);
    }
}
