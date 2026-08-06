<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\Client;

use App\Module\Appointment\Application\DTO\UpdateAppointmentStatusInput;
use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Application\Projection\AppointmentFormatter;
use App\Module\Appointment\Application\Workflow\AppointmentService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Security\AppointmentAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly AppointmentRepositoryPort $appointmentRepository,
        private readonly AppointmentService $appointmentService,
        private readonly AppointmentFormatter $appointmentFormatter,
        private readonly DtoValidator $dtoValidator,
        private readonly AppointmentAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());

        $appointment = $this->appointmentRepository->find($id);

        if (null === $appointment) {
            return ApiResponse::error('Rendez-vous introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessAppointment($user, $appointment)) {
            return ApiResponse::error('Vous n\'êtes pas autorisé à modifier ce rendez-vous.', Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
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
