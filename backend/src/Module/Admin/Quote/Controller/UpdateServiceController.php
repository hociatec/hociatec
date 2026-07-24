<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Admin\Quote\Service\QuoteServiceCatalogManager;
use App\Module\Admin\Quote\Service\QuoteServiceFormMapper;
use App\Module\Quote\Entity\Service;
use App\Module\Quote\Repository\ServiceRepository;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services/{id}', name: 'api_admin_services_update', methods: ['POST', 'PUT', 'PATCH'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class UpdateServiceController
{
    public function __construct(
        private ServiceRepository $repository,
        private QuoteServiceFormMapper $forms,
        private QuoteServiceCatalogManager $services,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $service = $this->repository->find($id);
        if (!$service instanceof Service) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $service = $this->services->update($service, $this->forms->update($request, $service));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return ApiResponse::error('Impossible de mettre a jour le service.', Response::HTTP_BAD_REQUEST, [$exception->getMessage()]);
        }

        return ApiResponse::success(QuoteFormatter::formatService($service));
    }
}
