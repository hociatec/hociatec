<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Repository\PrestationRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations/{id}', name: 'api_admin_appointments_prestations_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ShowPrestationController extends AbstractController
{
    public function __construct(private readonly PrestationRepository $prestationRepository)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $prestation = $this->prestationRepository->find($id);

        if ($prestation === null) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($this->mapPrestation($prestation));
    }

    private function mapPrestation(Prestation $prestation): array
    {
        return [
            'id' => $prestation->getId(),
            'name' => $prestation->getName(),
            'durationMinutes' => $prestation->getDurationMinutes(),
            'priceCents' => $prestation->getPriceCents(),
        ];
    }
}
