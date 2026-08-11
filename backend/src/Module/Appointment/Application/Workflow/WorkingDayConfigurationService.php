<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Application\DTO\WorkingDayData;
use App\Module\Appointment\Application\Exception\AppointmentOperationException;
use App\Module\Appointment\Application\Port\WorkingDayConfigurationPersistencePort;
use App\Module\Appointment\Application\Port\WorkingDayConfigurationRepositoryPort;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Shared\Domain\DateTime\DateTimeParser;

final class WorkingDayConfigurationService
{
    /**
     * @var array<int, string>
     */
    public const DAY_LABELS = [
        0 => 'Lundi',
        1 => 'Mardi',
        2 => 'Mercredi',
        3 => 'Jeudi',
        4 => 'Vendredi',
        5 => 'Samedi',
        6 => 'Dimanche',
    ];

    public function __construct(
        private readonly WorkingDayConfigurationRepositoryPort $repository,
        private readonly WorkingDayConfigurationPersistencePort $persistence,
    ) {
    }

    /**
     * @return list<WorkingDayConfiguration>
     */
    public function list(): array
    {
        $configurations = $this->repository->findAllOrdered();

        if (0 === count($configurations)) {
            $configurations = $this->seedDefaultConfiguration();
        }

        return $configurations;
    }

    /**
     * @param list<WorkingDayData> $payload
     *
     * @return list<WorkingDayConfiguration>
     */
    public function update(array $payload): array
    {
        $configurations = [];

        foreach ($payload as $item) {
            $day = $item->dayOfWeek;
            $configuration = $this->repository->findOneByDay($day);

            if (null === $configuration) {
                $configuration = new WorkingDayConfiguration($day, false);
                $this->persistence->save($configuration);
            }

            $isWorkingDay = $item->isWorkingDay;
            $configuration->setWorkingDay($isWorkingDay);

            if ($isWorkingDay) {
                $startTime = null !== $item->startTime
                    ? DateTimeParser::fromFormat('H:i', $item->startTime)
                    : null;
                $endTime = null !== $item->endTime
                    ? DateTimeParser::fromFormat('H:i', $item->endTime)
                    : null;

                if (null === $startTime || null === $endTime) {
                    throw new \InvalidArgumentException('Les heures de debut et fin doivent etre renseignees au format HH:MM.');
                }

                if ($endTime <= $startTime) {
                    throw new \InvalidArgumentException('L\'heure de fin doit etre posterieure a l\'heure de debut.');
                }

                $configuration->setStartTime($startTime);
                $configuration->setEndTime($endTime);

                $configuration->setBreaks($item->breaks);
            }

            $configurations[] = $configuration;
        }

        try {
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible de mettre a jour la configuration.', $exception);
        }

        return $configurations;
    }

    /**
     * @return list<WorkingDayConfiguration>
     */
    private function seedDefaultConfiguration(): array
    {
        $defaults = [];

        foreach (array_keys(self::DAY_LABELS) as $dayOfWeek) {
            $isWeekday = $dayOfWeek <= 4;

            if ($isWeekday) {
                $startTime = DateTimeParser::fromFormat('H:i', '09:00');
                $endTime = DateTimeParser::fromFormat('H:i', '19:00');
                $breaks = [
                    ['start' => '12:00', 'end' => '13:00'],
                ];

                $configuration = new WorkingDayConfiguration($dayOfWeek, true, $startTime, $endTime, $breaks);
            } else {
                $configuration = new WorkingDayConfiguration($dayOfWeek, false);
            }

            $this->persistence->save($configuration);
            $defaults[] = $configuration;
        }

        try {
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible de préparer la configuration par défaut.', $exception);
        }

        return $defaults;
    }
}
