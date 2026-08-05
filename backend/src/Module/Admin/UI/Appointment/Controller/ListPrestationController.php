<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Appointment\Controller;

use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations', name: 'api_admin_appointments_prestations_list', methods: ['GET'])]
#[IsGranted('ROLE_APPOINTMENTS_MANAGER')]
class ListPrestationController extends AbstractController
{
    public function __construct(private readonly PrestationService $prestationService)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 25, 100);
        $prestations = $this->prestationService->list($pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(static fn (Prestation $prestation) => [
                'id' => $prestation->getId(),
                'name' => $prestation->getName(),
                'durationMinutes' => $prestation->getDurationMinutes(),
                'priceCents' => $prestation->getPriceCents(),
            ], $prestations),
            $pagination->metadata($this->prestationService->count()),
        );
    }
}
