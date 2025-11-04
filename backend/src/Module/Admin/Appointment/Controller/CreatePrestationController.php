<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Service\PrestationService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/admin/appointments/prestations', name: 'api_admin_appointments_prestations_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreatePrestationController extends AbstractController
{
    public function __construct(private readonly PrestationService $prestationService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $name = (string) ($payload['name'] ?? '');
        $durationMinutes = (int) ($payload['durationMinutes'] ?? 0);
        $price = $payload['price'] ?? 0;

        $priceCents = $this->normalizePriceToCents($price);

        if ($priceCents < 0) {
            return ApiResponse::error('Le prix doit etre positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $prestation = $this->prestationService->create($name, $durationMinutes, $priceCents);
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible d\'enregistrer la prestation.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
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



