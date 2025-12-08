<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\Client;

use App\Module\Appointment\Repository\AppointmentRepository;
use App\Module\Appointment\Service\AppointmentService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments/{id}/cancel', name: 'api_appointments_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CancelAppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $appointment = $this->appointmentRepository->find($id);

        if (!$appointment) {
            throw new NotFoundHttpException('Rendez-vous non trouvé.');
        }

        // Vérifier que l'utilisateur est le propriétaire du rendez-vous ou est admin
        if ($appointment->getUser()->getId() !== $currentUser->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à annuler ce rendez-vous.');
        }

        try {
            $this->appointmentService->cancel($appointment);

            return ApiResponse::success([
                'message' => 'Rendez-vous annulé avec succès.',
            ]);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
