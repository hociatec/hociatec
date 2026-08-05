<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Appointment\Controller;

use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Module\Appointment\Application\Port\PrestationRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations/{id}', name: 'api_admin_appointments_prestations_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_APPOINTMENTS_MANAGER')]
class DeletePrestationController extends AbstractController
{
    public function __construct(
        private readonly PrestationRepositoryPort $prestationRepository,
        private readonly PrestationService $prestationService,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $prestation = $this->prestationRepository->find($id);

        if (null === $prestation) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->prestationService->delete($prestation);

        return ApiResponse::success(['id' => $id], JsonResponse::HTTP_OK, 'La prestation a bien été supprimée.');
    }
}
