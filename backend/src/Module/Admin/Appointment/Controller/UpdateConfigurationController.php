<?php

declare(strict_types=1);

namespace App\Module\Admin\Appointment\Controller;

use App\Module\Admin\Appointment\DTO\WorkingDaysInput;
use App\Module\Appointment\Exception\AppointmentOperationException;
use App\Module\Appointment\Service\WorkingDayConfigurationService;
use App\Module\Appointment\Service\WorkingDayPayloadMapper;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\InvalidJsonPayloadException;
use App\Shared\Validation\DtoValidator;
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
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
            $input = WorkingDaysInput::fromArray($payload);
            $this->validator->validate($input);
            $days = $this->payloadMapper->map($input->toPayload());
        } catch (InvalidJsonPayloadException|\JsonException|\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $configurations = $this->configurationService->update($days);
        } catch (\InvalidArgumentException|AppointmentOperationException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'days' => array_map(static fn ($configuration) => [
                'dayOfWeek' => $configuration->getDayOfWeek(),
                'dayLabel' => WorkingDayConfigurationService::DAY_LABELS[$configuration->getDayOfWeek()] ?? '',
                'isWorkingDay' => $configuration->isWorkingDay(),
                'startTime' => $configuration->getStartTime()?->format('H:i'),
                'endTime' => $configuration->getEndTime()?->format('H:i'),
                'breaks' => $configuration->getBreaks(),
            ], $configurations),
        ]);
    }
}
