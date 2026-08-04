<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\Client;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Appointment\Application\Service\AppointmentService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments/me', name: 'api_appointments_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAppointmentsController extends AbstractController
{
    public function __construct(private readonly AppointmentService $appointmentService)
    {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $appointments = $this->appointmentService->getAppointmentsForUser($user);

        return ApiResponse::success([
            'upcoming' => array_map($this->mapAppointment(...), $appointments['upcoming']),
            'past' => array_map($this->mapAppointment(...), $appointments['past']),
        ]);
    }

    /** @return array<string, mixed> */
    private function mapAppointment(Appointment $appointment): array
    {
        return [
            'id' => $appointment->getId(),
            'startAt' => $appointment->getStartAt()->format(DATE_ATOM),
            'endAt' => $appointment->getEndAt()->format(DATE_ATOM),
            'status' => $appointment->getStatusLabel(),
            'prestation' => [
                'id' => $appointment->getPrestation()->getId(),
                'name' => $appointment->getPrestation()->getName(),
                'durationMinutes' => $appointment->getPrestation()->getDurationMinutes(),
                'priceCents' => $appointment->getPrestation()->getPriceCents(),
            ],
        ];
    }
}
