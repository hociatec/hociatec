<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\Client;

use App\Module\Appointment\Application\Workflow\AppointmentService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments/me', name: 'api_appointments_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAppointmentsController extends AbstractController
{
    public function __construct(private readonly AppointmentService $appointmentService)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = Pagination::fromRequest($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();

        $appointments = $this->appointmentService->getPaginatedAppointmentsForUser($user, limit: $pagination->perPage, offset: $pagination->offset());
        $totals = $this->appointmentService->countAppointmentsForUser($user);

        return ApiResponse::success([
            'upcoming' => array_map($this->mapAppointment(...), $appointments['upcoming']),
            'past' => array_map($this->mapAppointment(...), $appointments['past']),
            'meta' => [
                'page' => $pagination->page,
                'perPage' => $pagination->perPage,
                'upcomingTotal' => $totals['upcoming'],
                'pastTotal' => $totals['past'],
            ],
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
