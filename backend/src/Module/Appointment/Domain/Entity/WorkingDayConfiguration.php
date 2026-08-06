<?php

declare(strict_types=1);

namespace App\Module\Appointment\Domain\Entity;

use App\Shared\Infrastructure\DateTime\DateTimeParser;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'appointment_working_days')]
#[ORM\UniqueConstraint(name: 'uniq_working_day_configuration_day', columns: ['day_of_week'])]
#[ORM\HasLifecycleCallbacks]
class WorkingDayConfiguration
{
    private const MINUTES_IN_DAY = 24 * 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    private int $dayOfWeek;

    #[ORM\Column(type: 'boolean')]
    private bool $isWorkingDay;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $startTime;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $endTime;

    /**
     * @var list<array{start: string, end: string}>
     */
    #[ORM\Column(type: 'json')]
    private array $breaks = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param list<array{start: string, end: string}> $breaks
     */
    public function __construct(
        int $dayOfWeek,
        bool $isWorkingDay,
        ?\DateTimeImmutable $startTime = null,
        ?\DateTimeImmutable $endTime = null,
        array $breaks = [],
    ) {
        $this->assertValidDayOfWeek($dayOfWeek);
        $this->dayOfWeek = $dayOfWeek;
        $this->isWorkingDay = $isWorkingDay;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->setBreaks($breaks);
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function isWorkingDay(): bool
    {
        return $this->isWorkingDay;
    }

    public function setWorkingDay(bool $workingDay): self
    {
        $this->isWorkingDay = $workingDay;

        if (!$workingDay) {
            $this->startTime = null;
            $this->endTime = null;
            $this->breaks = [];
        }

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    public function getBreaks(): array
    {
        return $this->breaks;
    }

    /**
     * @param list<array{start: string, end: string}> $breaks
     */
    public function setBreaks(array $breaks): self
    {
        foreach ($breaks as $break) {
            $startMinutes = $this->timeToMinutes($break['start']);
            $endMinutes = $this->timeToMinutes($break['end']);

            if ($endMinutes <= $startMinutes) {
                throw new \InvalidArgumentException('Break end time must be greater than start time.');
            }
        }

        $this->breaks = $breaks;

        return $this;
    }

    public function hasWorkingHours(): bool
    {
        return $this->isWorkingDay && null !== $this->startTime && null !== $this->endTime;
    }

    /**
     * @return list<array{start: \DateTimeImmutable, end: \DateTimeImmutable}>
     */
    public function getBreakIntervalsForDate(\DateTimeImmutable $date): array
    {
        if (!$this->hasWorkingHours()) {
            return [];
        }

        $intervals = [];

        foreach ($this->breaks as $break) {
            $start = DateTimeParser::fromFormat(
                'Y-m-d H:i',
                sprintf('%s %s', $date->format('Y-m-d'), $break['start'])
            );
            $end = DateTimeParser::fromFormat(
                'Y-m-d H:i',
                sprintf('%s %s', $date->format('Y-m-d'), $break['end'])
            );

            if (!$start || !$end) {
                continue;
            }

            $intervals[] = ['start' => $start, 'end' => $end];
        }

        return $intervals;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function assertValidDayOfWeek(int $dayOfWeek): void
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException('Day of week must be between 0 (Monday) and 6 (Sunday).');
        }
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        $total = $hours * 60 + $minutes;

        if ($total < 0 || $total > self::MINUTES_IN_DAY) {
            throw new \InvalidArgumentException('Time must be within 00:00 and 24:00.');
        }

        return $total;
    }
}
