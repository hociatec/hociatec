<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Service\WorkingDayConfigurationService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/configuration', name: 'api_admin_appointments_configuration_get', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class GetConfigurationController extends AbstractController
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



