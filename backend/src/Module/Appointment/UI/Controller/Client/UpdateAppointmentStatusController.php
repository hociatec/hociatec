<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\Client;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Appointment\Application\DTO\UpdateAppointmentStatusInput;
use App\Module\Appointment\Application\Service\AppointmentFormatter;
use App\Module\Appointment\Application\Service\AppointmentService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Security\AppointmentAccessPolicy;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments/{id}/status', name: 'api_appointments_update_status', methods: ['PATCH'])]
#[IsGranted('ROLE_USER')]
final class UpdateAppointmentStatusController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentFormatter $appointmentFormatter,
        private readonly DtoValidator $dtoValidator,
        private readonly AppointmentAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $appointment = $this->appointmentRepository->find($id);

        if (null === $appointment) {
            return ApiResponse::error('Rendez-vous introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessAppointment($user, $appointment)) {
            return ApiResponse::error('Vous n\'êtes pas autorisé à modifier ce rendez-vous.', Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = UpdateAppointmentStatusInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        try {
            $this->appointmentService->changeStatus($appointment, $input->status);
        } catch (\DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'appointment' => $this->appointmentFormatter->format($appointment),
        ]);
    }

    private function canAccessAppointment(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin() || $this->accessPolicy->canChangeStatus($user, $appointment);
    }
}
