<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Repository\WorkingDayConfigurationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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
        private readonly WorkingDayConfigurationRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<WorkingDayConfiguration>
     */
    public function list(): array
    {
        $configurations = $this->repository->findAllOrdered();

        if (count($configurations) === 0) {
            $configurations = $this->seedDefaultConfiguration();
        }

        return $configurations;
    }

    /**
     * @param list<array{
     *     dayOfWeek: int,
     *     isWorkingDay: bool,
     *     startTime?: ?string,
     *     endTime?: ?string,
     *     breaks?: list<array{start: string, end: string}>
     * }> $payload
     *
     * @return list<WorkingDayConfiguration>
     */
    public function update(array $payload): array
    {
        $configurations = [];

        foreach ($payload as $item) {
            $day = $item['dayOfWeek'];
            $configuration = $this->repository->findOneByDay($day);

            if ($configuration === null) {
                $configuration = new WorkingDayConfiguration($day, false);
                $this->entityManager->persist($configuration);
            }

            $isWorkingDay = (bool) $item['isWorkingDay'];
            $configuration->setWorkingDay($isWorkingDay);

            if ($isWorkingDay) {
                $startTime = isset($item['startTime']) && $item['startTime'] !== null
                    ? DateTimeImmutable::createFromFormat('H:i', $item['startTime'])
                    : null;
                $endTime = isset($item['endTime']) && $item['endTime'] !== null
                    ? DateTimeImmutable::createFromFormat('H:i', $item['endTime'])
                    : null;

                if ($startTime === false || $endTime === false || $startTime === null || $endTime === null) {
                    throw new InvalidArgumentException('Les heures de debut et fin doivent etre renseignees au format HH:MM.');
                }

                if ($endTime <= $startTime) {
                    throw new InvalidArgumentException('L\'heure de fin doit etre posterieure a l\'heure de debut.');
                }

                $configuration->setStartTime($startTime);
                $configuration->setEndTime($endTime);

                /** @var list<array{start: string, end: string}> $breaks */
                $breaks = $item['breaks'] ?? [];
                $configuration->setBreaks($breaks);
            }

            $configurations[] = $configuration;
        }

        $this->entityManager->flush();

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
                $startTime = DateTimeImmutable::createFromFormat('H:i', '09:00') ?: null;
                $endTime = DateTimeImmutable::createFromFormat('H:i', '19:00') ?: null;
                $breaks = [
                    ['start' => '12:00', 'end' => '13:00'],
                ];

                $configuration = new WorkingDayConfiguration($dayOfWeek, true, $startTime, $endTime, $breaks);
            } else {
                $configuration = new WorkingDayConfiguration($dayOfWeek, false);
            }

            $this->entityManager->persist($configuration);
            $defaults[] = $configuration;
        }

        $this->entityManager->flush();

        return $defaults;
    }
}

