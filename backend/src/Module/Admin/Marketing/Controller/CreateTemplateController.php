<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Controller;

use App\Module\Admin\Marketing\DTO\MarketingTemplateInput;
use App\Module\Admin\Marketing\Service\EmailTemplateAdminManager;
use App\Module\Marketing\Entity\EmailTemplate;
use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Marketing\Service\EmailTemplateScenarioProvider;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/marketing/templates', name: 'api_admin_marketing_templates_create', methods: ['POST'])]
#[IsGranted('ROLE_MARKETING_MANAGER')]
final class CreateTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailTemplateAdminManager $manager,
        private readonly EmailTemplateRepository $templates,
        private readonly EmailTemplateScenarioProvider $scenarioProvider,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = MarketingTemplateInput::fromArray($payload);
        $this->validator->validate($input);

        if (!isset($this->scenarioProvider->getTemplateScenarioDefinitions()[$input->scenarioKey])) {
            return ApiResponse::error('Scénario de template invalide.');
        }

        if (null !== $this->templates->findOneBySlug($input->slug)) {
            return ApiResponse::error('Ce slug de template est déjà utilisé.');
        }

        $template = new EmailTemplate($input->name, $input->slug, $input->scenarioKey, $input->subjectTemplate, $input->htmlBody, $input->textBody);
        $template->setIsActive($input->isActive);

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
        ], 'Le modèle d’e-mail a bien été créé.');
    }
}
