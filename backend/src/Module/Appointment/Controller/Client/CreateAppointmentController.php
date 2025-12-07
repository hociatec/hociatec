<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\Client;

use App\Module\Appointment\Repository\PrestationRepository;
use App\Module\Appointment\Service\AppointmentService;
use App\Module\Appointment\Service\AppointmentFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/appointments', name: 'api_appointments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentFormatter $appointmentFormatter,
        private readonly PrestationRepository $prestationRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $prestationId = (int) ($payload['prestationId'] ?? 0);
        $start = $payload['startAt'] ?? null;

        if ($prestationId === 0 || $start === null) {
            return ApiResponse::error('Les champs "prestationId" et "startAt" sont requis.', Response::HTTP_BAD_REQUEST);
        }

        $prestation = $this->prestationRepository->find($prestationId);

        if ($prestation === null) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $startAt = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, (string) $start)
            ?: new DateTimeImmutable((string) $start);

        /** @var User $user */
        $user = $this->getUser();

        try {
            $appointment = $this->appointmentService->book($user, $prestation, $startAt);
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible de reserver ce creneau.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::created($this->appointmentFormatter->format($appointment));
    }
}


