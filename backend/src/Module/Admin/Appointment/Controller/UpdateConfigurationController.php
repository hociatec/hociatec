<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Appointment\Service\WorkingDayConfigurationService;
use App\Module\Appointment\Service\WorkingDayPayloadMapper;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments/configuration', name: 'api_admin_appointments_configuration_update', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateConfigurationController extends AbstractController
{
    public function __construct(
        private readonly WorkingDayConfigurationService $configurationService,
        private readonly WorkingDayPayloadMapper $payloadMapper,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $days = $this->payloadMapper->map($payload);
        } catch (\JsonException|\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $configurations = $this->configurationService->update($days);
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                'Impossible de mettre a jour la configuration.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success([
            'days' => array_map(static fn ($configuration) => [
                'dayOfWeek' => $configuration->getDayOfWeek(),
                'isWorkingDay' => $configuration->isWorkingDay(),
                'startTime' => $configuration->getStartTime()?->format('H:i'),
                'endTime' => $configuration->getEndTime()?->format('H:i'),
                'breaks' => $configuration->getBreaks(),
            ], $configurations),
        ]);
    }
}
