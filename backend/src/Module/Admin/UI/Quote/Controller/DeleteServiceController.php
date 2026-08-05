<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Admin\Application\Quote\Handler\DeleteQuoteServiceHandler;
use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services/{id}', name: 'api_admin_services_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteServiceController extends AbstractController
{
    public function __construct(
        private readonly ServiceOfferingRepositoryPort $serviceRepository,
        private readonly DeleteQuoteServiceHandler $deleteService,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (null === $service) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->deleteService->delete($service);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'Le service a bien été supprimé.');
    }
}
