<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\Client;

use App\Module\Appointment\Application\DTO\RescheduleAppointmentInput;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\Appointment\Application\Workflow\CustomerAppointmentPortalService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/appointments/{id}/reschedule', name: 'api_appointments_reschedule', methods: ['PATCH'])]
#[IsGranted('ROLE_USER')]
final class RescheduleAppointmentController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerAppointmentPortalService $portal,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = RescheduleAppointmentInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        try {
            $startAt = new \DateTimeImmutable($input->startAt);
        } catch (\DateMalformedStringException) {
            return ApiResponse::error('Date de report invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $appointment = $this->portal->rescheduleForUser($this->currentUser(), $id, $startAt);
        } catch (\DomainException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Report impossible.', Response::HTTP_FORBIDDEN);
        } catch (InvalidAppointmentSlotException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Créneau indisponible.', Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Report impossible.', Response::HTTP_BAD_REQUEST);
        }

        if (null === $appointment) {
            return ApiResponse::error('Rendez-vous introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'appointment' => $appointment,
        ]);
    }
}
