<?php

declare(strict_types=1);

namespace App\Module\Appointment\Controller\PublicApi;

use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Service\PrestationService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/appointments/prestations', name: 'api_public_appointments_prestations', methods: ['GET'])]
#[RateLimiter('public_api')]
class PublicPrestationController extends AbstractController
{
    public function __construct(private readonly PrestationService $prestationService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $prestations = $this->prestationService->list();

        return ApiResponse::success([
            'items' => array_map(static fn (Prestation $prestation) => [
                'id' => $prestation->getId(),
                'name' => $prestation->getName(),
                'durationMinutes' => $prestation->getDurationMinutes(),
                'priceCents' => $prestation->getPriceCents(),
            ], $prestations),
        ]);
    }
}



