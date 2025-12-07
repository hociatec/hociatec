<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\Client;

use App\Module\Appointment\Service\AppointmentService;
use App\Module\Appointment\Service\AppointmentFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments/me', name: 'api_appointments_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAppointmentsController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentFormatter $appointmentFormatter,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $status = $request->query->get('status');

        if (!is_string($status)) {
            $status = null;
        }

        try {
            $appointments = $this->appointmentService->getAppointmentsForUser($user, $status);
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'upcoming' => array_map($this->appointmentFormatter->format(...), $appointments['upcoming']),
            'past' => array_map($this->appointmentFormatter->format(...), $appointments['past']),
        ]);
    }
}
