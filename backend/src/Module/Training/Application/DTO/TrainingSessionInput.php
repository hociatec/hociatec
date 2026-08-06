<?php

declare(strict_types=1);

namespace App\Module\Training\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TrainingSessionInput
{
    #[Assert\Positive]
    public int $trainingId;
    public \DateTimeImmutable $startsAt;
    public \DateTimeImmutable $endsAt;
    public \DateTimeImmutable $dailyStartTime;
    public \DateTimeImmutable $dailyEndTime;
    public bool $includeWeekends;
    #[Assert\Choice(choices: ['onsite', 'remote'])]
    public string $format;
    #[Assert\Positive]
    public int $capacity;
    public ?string $location;
    public ?string $meetingUrl;
    public string $status;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->trainingId = (int) $data['trainingId'];
        $this->startsAt = $data['startsAt'];
        $this->endsAt = $data['endsAt'];
        $this->dailyStartTime = $data['dailyStartTime'];
        $this->dailyEndTime = $data['dailyEndTime'];
        $this->includeWeekends = (bool) $data['includeWeekends'];
        $this->format = (string) $data['format'];
        $this->capacity = (int) $data['capacity'];
        $this->location = $data['location'];
        $this->meetingUrl = $data['meetingUrl'];
        $this->status = (string) $data['status'];
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

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $now = new \DateTimeImmutable();
        $keys = ['trainingId', 'startsAt', 'endsAt', 'dailyStartTime', 'dailyEndTime', 'includeWeekends', 'format', 'capacity', 'location', 'meetingUrl', 'status'];
        $defaults = array_fill_keys($keys, null);
        $defaults['trainingId'] = 0;
        $defaults['startsAt'] = $now;
        $defaults['endsAt'] = $now->modify('+1 day');
        $defaults['dailyStartTime'] = new \DateTimeImmutable('08:00');
        $defaults['dailyEndTime'] = new \DateTimeImmutable('20:00');
        $defaults['includeWeekends'] = true;
        $defaults['format'] = 'onsite';
        $defaults['capacity'] = 1;
        $defaults['status'] = 'scheduled';
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
