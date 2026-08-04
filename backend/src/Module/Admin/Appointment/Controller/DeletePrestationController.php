<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Repository\PrestationRepository;
use App\Module\Appointment\Service\PrestationService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations/{id}', name: 'api_admin_appointments_prestations_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeletePrestationController extends AbstractController
{
    public function __construct(
        private readonly PrestationRepository $prestationRepository,
        private readonly PrestationService $prestationService,
    )
    {
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
