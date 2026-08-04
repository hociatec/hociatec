<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Module\Admin\Application\Marketing\Service\EmailTemplateWriter;
use App\Module\Admin\UI\Marketing\Http\MarketingRequestMapper;
use App\Module\Marketing\Infrastructure\Http\EmailTemplateResponseFormatter;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly EmailTemplateRepository $templates,
        private readonly EmailTemplateWriter $writer,
        private readonly DtoValidator $validator,
        private readonly MarketingRequestMapper $requests,
        private readonly EmailTemplateResponseFormatter $formatter,
    ) {
    }

    public function __invoke(int $templateId, Request $request): JsonResponse
    {
        $template = $this->templates->find($templateId);
        if (null === $template) {
            return ApiResponse::error('Template introuvable.', Response::HTTP_NOT_FOUND);
        }

        $input = $this->requests->template($request);
        $this->validator->validate($input);

        try {
            $template = $this->writer->update($template, $input);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return ApiResponse::success(['template' => $this->formatter->format($template)], JsonResponse::HTTP_OK, 'Le modèle d’e-mail a bien été mis à jour.');
    }
}
