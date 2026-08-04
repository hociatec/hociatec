<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\PublicApi;

use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Module\Appointment\UI\Response\PublicAppointmentResponseMapper;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/appointments/prestations', name: 'api_public_appointments_prestations', methods: ['GET'])]
#[RateLimited('public_api')]
class PublicPrestationController extends AbstractController
{
    public function __construct(
        private readonly PrestationService $prestationService,
        private readonly PublicAppointmentResponseMapper $responses,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $prestations = $this->prestationService->list();

        return ApiResponse::success($this->responses->prestations($prestations));
    }
}
