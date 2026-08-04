<?php

declare(strict_types=1);

namespace App\Module\Appointment\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationService;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/appointments/schedule', name: 'api_public_appointments_schedule', methods: ['GET'])]
#[RateLimited('public_api')]
class PublicWorkingDayController extends AbstractController
{
    public function __construct(private readonly WorkingDayConfigurationService $configurationService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $configurations = $this->configurationService->list();

        return ApiResponse::success([
            'days' => array_map(static fn (WorkingDayConfiguration $configuration) => [
                'dayOfWeek' => $configuration->getDayOfWeek(),
                'isWorkingDay' => $configuration->isWorkingDay(),
                'startTime' => $configuration->getStartTime()?->format('H:i'),
                'endTime' => $configuration->getEndTime()?->format('H:i'),
                'breaks' => $configuration->getBreaks(),
            ], $configurations),
        ]);
    }
}
