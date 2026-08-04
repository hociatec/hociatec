<?php

declare(strict_types=1);

namespace App\Module\Training\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TrainingSessionInput
{
    public function __construct(
        #[Assert\Positive] public int $trainingId,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public \DateTimeImmutable $dailyStartTime,
        public \DateTimeImmutable $dailyEndTime,
        public bool $includeWeekends,
        #[Assert\Choice(choices: ['onsite', 'remote'])] public string $format,
        #[Assert\Positive] public int $capacity,
        public ?string $location,
        public ?string $meetingUrl,
        public string $status,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        try {
            $startsAt = new \DateTimeImmutable(is_string($payload['startsAt'] ?? null) ? $payload['startsAt'] : 'now');
            $endsAt = new \DateTimeImmutable(is_string($payload['endsAt'] ?? null) ? $payload['endsAt'] : $startsAt->modify('+1 day')->format(\DateTimeInterface::ATOM));
            $dailyStart = new \DateTimeImmutable(is_string($payload['dailyStartTime'] ?? null) ? $payload['dailyStartTime'] : '08:00');
            $dailyEnd = new \DateTimeImmutable(is_string($payload['dailyEndTime'] ?? null) ? $payload['dailyEndTime'] : '20:00');
        } catch (\DateMalformedStringException $exception) {
            throw new \InvalidArgumentException('Dates de session invalides.', previous: $exception);
        }

        return new self(
            is_numeric($payload['trainingId'] ?? null) ? (int) $payload['trainingId'] : 0,
            $startsAt,
            $endsAt,
            $dailyStart,
            $dailyEnd,
            is_bool($payload['includeWeekends'] ?? null) ? $payload['includeWeekends'] : true,
            in_array($payload['format'] ?? null, ['onsite', 'remote'], true) ? $payload['format'] : 'onsite',
            max(1, is_numeric($payload['capacity'] ?? null) ? (int) $payload['capacity'] : 1),
            is_string($payload['location'] ?? null) ? trim($payload['location']) : null,
            is_string($payload['meetingUrl'] ?? null) ? trim($payload['meetingUrl']) : null,
            is_string($payload['status'] ?? null) && '' !== trim($payload['status']) ? trim($payload['status']) : 'scheduled',
        );
    }
}
