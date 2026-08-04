<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\Client;

use App\Module\Appointment\DTO\CreateAppointmentInput;
use App\Module\Appointment\Repository\PrestationRepository;
use App\Module\Appointment\Service\AppointmentFormatter;
use App\Module\Appointment\Service\AppointmentService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments', name: 'api_appointments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentFormatter $appointmentFormatter,
        private readonly PrestationRepository $prestationRepository,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (\Exception) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = CreateAppointmentInput::fromArray($payload);
            $this->dtoValidator->validate($input);
        } catch (\Exception $exception) {
            return ApiResponse::error('Donnees de rendez-vous invalides.', Response::HTTP_UNPROCESSABLE_ENTITY, [$exception->getMessage()]);
        }

        $prestation = $this->prestationRepository->find($input->prestationId);

        if (null === $prestation) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $startAt = new \DateTimeImmutable($input->startAt);
        } catch (\Exception) {
            return ApiResponse::error('La date de debut est invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $appointment = $this->appointmentService->book($user, $prestation, $startAt);
        } catch (\Exception $exception) {
            return ApiResponse::error(
                'Impossible de reserver ce creneau.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::created($this->appointmentFormatter->format($appointment));
    }
}
