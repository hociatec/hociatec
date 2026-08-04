<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\Client;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Appointment\Application\DTO\CreateAppointmentInput;
use App\Module\Appointment\Application\Exception\AppointmentOperationException;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\Appointment\Application\Projection\AppointmentFormatter;
use App\Module\Appointment\Application\Service\AppointmentService;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\User\Domain\Entity\User;
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
            $payload = \App\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = CreateAppointmentInput::fromArray($payload);
            $this->dtoValidator->validate($input);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error('Donnees de rendez-vous invalides.', Response::HTTP_UNPROCESSABLE_ENTITY, [$exception->getMessage()]);
        }

        $prestation = $this->prestationRepository->find($input->prestationId);

        if (null === $prestation) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $startAt = new \DateTimeImmutable($input->startAt);
        } catch (\DateMalformedStringException) {
            return ApiResponse::error('La date de debut est invalide.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $appointment = $this->appointmentService->book($user, $prestation, $startAt);
        } catch (InvalidAppointmentSlotException|AppointmentOperationException $exception) {
            return ApiResponse::error(
                'Impossible de reserver ce creneau.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::created($this->appointmentFormatter->format($appointment));
    }
}
