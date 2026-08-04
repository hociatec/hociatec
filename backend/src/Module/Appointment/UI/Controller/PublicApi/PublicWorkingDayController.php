<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\PublicApi;

use App\Module\Appointment\Application\Workflow\WorkingDayConfigurationService;
use App\Module\Appointment\UI\Response\PublicAppointmentResponseMapper;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/appointments/schedule', name: 'api_public_appointments_schedule', methods: ['GET'])]
#[RateLimited('public_api')]
class PublicWorkingDayController extends AbstractController
{
    public function __construct(
        private readonly WorkingDayConfigurationService $configurationService,
        private readonly PublicAppointmentResponseMapper $responses,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $configurations = $this->configurationService->list();

        return ApiResponse::success($this->responses->workingDays($configurations));
    }
}
