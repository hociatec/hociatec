<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\PublicApi;

use App\Module\Appointment\Application\Workflow\AvailabilityService;
use App\Module\Appointment\Application\Port\PrestationRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/appointments/availability', name: 'api_public_appointments_availability', methods: ['GET'])]
#[RateLimited('public_api')]
class PublicAvailabilityController extends AbstractController
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PrestationRepositoryPort $prestationRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $startAt = RequestQueryMapper::dateTime($request, 'start');
        $endAt = RequestQueryMapper::dateTime($request, 'end');
        $prestationId = RequestQueryMapper::requiredInt($request, 'prestationId');

        if (null === $startAt || null === $endAt || null === $prestationId) {
            return ApiResponse::error('Parametres requis: start, end, prestationId.', Response::HTTP_BAD_REQUEST);
        }

        if ($endAt <= $startAt) {
            return ApiResponse::error('La periode fournie est invalide.', Response::HTTP_BAD_REQUEST);
        }

        $prestation = $this->prestationRepository->find($prestationId);

        if (null === $prestation) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $slots = $this->availabilityService->getAvailableSlots($startAt, $endAt, $prestation);

        return ApiResponse::success([
            'slots' => $slots,
        ]);
    }
}
