<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\DTO;

final readonly class WorkingDayData
{
    /**
     * @param list<array{start: string, end: string}> $breaks
     */
    public function __construct(
        public int $dayOfWeek,
        public bool $isWorkingDay,
        public ?string $startTime,
        public ?string $endTime,
        public array $breaks = [],
    ) {
    }
}
