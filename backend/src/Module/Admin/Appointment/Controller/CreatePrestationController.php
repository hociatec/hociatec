<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Admin\Appointment\DTO\PrestationInput;
use App\Module\Appointment\Service\PrestationService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/prestations', name: 'api_admin_appointments_prestations_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreatePrestationController extends AbstractController
{
    public function __construct(private readonly PrestationService $prestationService, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PrestationInput::fromArray($payload);
        $this->validator->validate($input);
        $name = $input->name;
        $durationMinutes = $input->durationMinutes;
        $price = $input->price;

        $priceCents = $this->normalizePriceToCents($price);

        if ($priceCents < 0) {
            return ApiResponse::error('Le prix doit etre positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $prestation = $this->prestationService->create($name, $durationMinutes, $priceCents);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return ApiResponse::internalError('Impossible d\'enregistrer la prestation.');
        }

        return ApiResponse::created([
            'id' => $prestation->getId(),
            'name' => $prestation->getName(),
            'durationMinutes' => $prestation->getDurationMinutes(),
            'priceCents' => $prestation->getPriceCents(),
        ]);
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
