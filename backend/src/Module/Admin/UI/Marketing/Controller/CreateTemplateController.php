<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Marketing\Service\EmailTemplateWriter;
use App\Module\Admin\UI\Marketing\Http\MarketingRequestMapper;
use App\Module\Marketing\Infrastructure\Http\EmailTemplateResponseFormatter;
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
        private readonly EmailTemplateWriter $writer,
        private readonly DtoValidator $validator,
        private readonly MarketingRequestMapper $requests,
        private readonly EmailTemplateResponseFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $input = $this->requests->template($request);
        $this->validator->validate($input);

        try {
            $template = $this->writer->create($input);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage());
        }

        return ApiResponse::created(['template' => $this->formatter->format($template)], 'Le modèle d’e-mail a bien été créé.');
    }
}
