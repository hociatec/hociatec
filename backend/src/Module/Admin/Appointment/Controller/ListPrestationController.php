<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Service\PrestationService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations', name: 'api_admin_appointments_prestations_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListPrestationController extends AbstractController
{
    public function __construct(private readonly PrestationService $prestationService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $prestations = $this->prestationService->list();

        return ApiResponse::success([
            'items' => array_map(static fn (Prestation $prestation) => [
                'id' => $prestation->getId(),
                'name' => $prestation->getName(),
                'durationMinutes' => $prestation->getDurationMinutes(),
                'priceCents' => $prestation->getPriceCents(),
            ], $prestations),
        ]);
    }
}
