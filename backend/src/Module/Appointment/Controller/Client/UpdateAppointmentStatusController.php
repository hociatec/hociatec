<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\Client;

use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Appointment\Service\AppointmentFormatter;
use App\Module\Appointment\Service\AppointmentService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/appointments/{id}/status', name: 'api_appointments_update_status', methods: ['PATCH'])]
#[IsGranted('ROLE_USER')]
final class UpdateAppointmentStatusController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentFormatter $appointmentFormatter,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $appointment = $this->appointmentRepository->find($id);

        if ($appointment === null || $appointment->getUser()->getId() !== $user->getId()) {
            return ApiResponse::error('Rendez-vous introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $status = isset($payload['status']) ? trim((string) $payload['status']) : '';

        if ($status === '') {
            return ApiResponse::error('Le champ "status" est requis.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->appointmentService->changeStatus($appointment, $status);
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'appointment' => $this->appointmentFormatter->format($appointment),
        ]);
    }
}
