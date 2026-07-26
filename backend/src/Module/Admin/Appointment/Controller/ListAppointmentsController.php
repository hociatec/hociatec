<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Entity\Appointment;
use App\Module\Appointment\Repository\AppointmentRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments', name: 'api_admin_appointments_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListAppointmentsController extends AbstractController
{
    public function __construct(private readonly AppointmentRepository $appointmentRepository)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $appointments = $this->appointmentRepository->findBy([], ['startAt' => 'DESC'], $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(static fn (Appointment $appointment) => [
                'id' => $appointment->getId(),
                'startAt' => $appointment->getStartAt()->format(DATE_ATOM),
                'endAt' => $appointment->getEndAt()->format(DATE_ATOM),
                'status' => $appointment->getStatusLabel(),
                'user' => [
                    'id' => $appointment->getUser()->getId(),
                    'email' => $appointment->getUser()->getEmail(),
                ],
                'prestation' => [
                    'id' => $appointment->getPrestation()->getId(),
                    'name' => $appointment->getPrestation()->getName(),
                    'durationMinutes' => $appointment->getPrestation()->getDurationMinutes(),
                    'priceCents' => $appointment->getPrestation()->getPriceCents(),
                ],
            ], $appointments),
            $pagination->metadata($this->appointmentRepository->count([])),
        );
    }
}
