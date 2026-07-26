<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Admin\Quote\Service\QuoteServiceCatalogManager;
use App\Module\Admin\Quote\Service\QuoteServiceFormMapper;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services', name: 'api_admin_services_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class CreateServiceController
{
    public function __construct(
        private QuoteServiceFormMapper $forms,
        private QuoteServiceCatalogManager $services,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $service = $this->services->create($this->forms->create($request));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return ApiResponse::internalError('Impossible de créer le service.');
        }

        return ApiResponse::created(QuoteFormatter::formatService($service), 'Le service a bien été créé.');
    }
}
