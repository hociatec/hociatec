<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Appointment\Controller;

use App\Module\Admin\Application\Appointment\DTO\PrestationInput;
use App\Module\Appointment\Application\Exception\AppointmentOperationException;
use App\Module\Appointment\Application\Port\PrestationRepositoryPort;
use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\RequestPayloadMapper;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations/{id}', name: 'api_admin_appointments_prestations_update', methods: ['PUT'])]
#[IsGranted('ROLE_APPOINTMENTS_MANAGER')]
class UpdatePrestationController extends AbstractController
{
    public function __construct(
        private readonly PrestationRepositoryPort $prestationRepository,
        private readonly PrestationService $prestationService,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $prestation = $this->prestationRepository->find($id);

        if (null === $prestation) {
            return ApiResponse::error('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PrestationInput::fromArray($payload);
        $this->validator->validate($input);
        try {
            $priceCents = RequestPayloadMapper::priceCents($input->price);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromInvalidArgument($exception, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($priceCents < 0) {
            return ApiResponse::error('Le prix doit etre positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $prestation = $this->prestationService->update($prestation, $input->name, $input->durationMinutes, $priceCents);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromInvalidArgument($exception, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AppointmentOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::success([
            'id' => $prestation->getId(),
            'name' => $prestation->getName(),
            'durationMinutes' => $prestation->getDurationMinutes(),
            'priceCents' => $prestation->getPriceCents(),
        ], JsonResponse::HTTP_OK, 'La prestation a bien été mise à jour.');
    }
}
