<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\PublicApi;

use App\Module\Appointment\Repository\PrestationRepository;
use App\Module\Appointment\Service\AvailabilityService;
use App\Shared\Http\ApiResponse;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/appointments/availability', name: 'api_public_appointments_availability', methods: ['GET'])]
#[RateLimiter('public_api')]
class PublicAvailabilityController extends AbstractController
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PrestationRepository $prestationRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        $prestationId = $request->query->getInt('prestationId');

        if ($start === null || $end === null || $prestationId === 0) {
            return ApiResponse::error('Parametres requis: start, end, prestationId.', Response::HTTP_BAD_REQUEST);
        }

        $startAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $start) ?: new DateTimeImmutable($start);
        $endAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $end) ?: new DateTimeImmutable($end);

        if ($endAt <= $startAt) {
            return ApiResponse::error('La periode fournie est invalide.', Response::HTTP_BAD_REQUEST);
        }

        $prestation = $this->prestationRepository->find($prestationId);

        if ($prestation === null) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $slots = $this->availabilityService->getAvailableSlots($startAt, $endAt, $prestation);

        return ApiResponse::success([
            'slots' => $slots,
        ]);
    }
}





