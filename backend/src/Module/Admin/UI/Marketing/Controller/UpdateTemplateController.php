<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Marketing\DTO\MarketingTemplateInput;
use App\Module\Admin\Application\Marketing\Service\EmailTemplateAdminManager;
use App\Module\Marketing\Application\Service\EmailTemplateScenarioProvider;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates/{templateId}', name: 'api_admin_marketing_templates_update', methods: ['PUT'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class UpdateTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailTemplateAdminManager $manager,
        private readonly EmailTemplateRepository $templates,
        private readonly EmailTemplateScenarioProvider $scenarioProvider,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $templateId, Request $request): JsonResponse
    {
        $template = $this->templates->find($templateId);
        if (null === $template) {
            return ApiResponse::error('Template introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
        $input = MarketingTemplateInput::fromArray($payload);
        $this->validator->validate($input);

        if (!isset($this->scenarioProvider->getTemplateScenarioDefinitions()[$input->scenarioKey])) {
            return ApiResponse::error('Scénario de template invalide.');
        }

        $existing = $this->templates->findOneBySlug($input->slug);
        if (null !== $existing && $existing->getId() !== $template->getId()) {
            return ApiResponse::error('Ce slug de template est déjà utilisé.');
        }

        $template
            ->setName($input->name)
            ->setSlug($input->slug)
            ->setScenarioKey($input->scenarioKey)
            ->setSubjectTemplate($input->subjectTemplate)
            ->setHtmlBody($input->htmlBody)
            ->setTextBody($input->textBody)
            ->setIsActive($input->isActive);

        $this->manager->save($template);

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
        ], JsonResponse::HTTP_OK, 'Le modèle d’e-mail a bien été mis à jour.');
    }
}
