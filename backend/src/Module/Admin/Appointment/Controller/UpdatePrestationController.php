<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Admin\Appointment\DTO\PrestationInput;
use App\Module\Appointment\Exception\AppointmentOperationException;
use App\Module\Appointment\Repository\PrestationRepository;
use App\Module\Appointment\Service\PrestationService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\InvalidJsonPayloadException;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations/{id}', name: 'api_admin_appointments_prestations_update', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
class UpdatePrestationController extends AbstractController
{
    public function __construct(
        private readonly PrestationRepository $prestationRepository,
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
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PrestationInput::fromArray($payload);
        $this->validator->validate($input);
        $priceCents = $this->normalizePriceToCents($input->price);

        if ($priceCents < 0) {
            return ApiResponse::error('Le prix doit etre positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $prestation = $this->prestationService->update($prestation, $input->name, $input->durationMinutes, $priceCents);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AppointmentOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success([
            'id' => $prestation->getId(),
            'name' => $prestation->getName(),
            'durationMinutes' => $prestation->getDurationMinutes(),
            'priceCents' => $prestation->getPriceCents(),
        ], JsonResponse::HTTP_OK, 'La prestation a bien été mise à jour.');
    }

    private function normalizePriceToCents(mixed $price): int
    {
        if (is_int($price)) {
            return $price * 100;
        }

        if (is_float($price)) {
            return (int) round($price * 100);
        }

        if (is_string($price)) {
            $normalized = str_replace(',', '.', $price);

            if (is_numeric($normalized)) {
                return (int) round((float) $normalized * 100);
            }
        }

        return -1;
    }
}
