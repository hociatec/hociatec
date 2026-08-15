<?php

declare(strict_types=1);

namespace App\Module\Training\Application\DTO;

use App\Shared\Domain\DateTime\DateTimeParser;
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

    /**
     * @param array{
     *   trainingId?: int,
     *   startsAt?: \DateTimeImmutable,
     *   endsAt?: \DateTimeImmutable,
     *   dailyStartTime?: \DateTimeImmutable,
     *   dailyEndTime?: \DateTimeImmutable,
     *   includeWeekends?: bool,
     *   format?: string,
     *   capacity?: int,
     *   location?: ?string,
     *   meetingUrl?: ?string,
     *   status?: string
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $now = new \DateTimeImmutable();
        $data = array_replace([
            'trainingId' => 0,
            'startsAt' => $now,
            'endsAt' => $now->modify('+1 day'),
            'dailyStartTime' => new \DateTimeImmutable('08:00'),
            'dailyEndTime' => new \DateTimeImmutable('20:00'),
            'includeWeekends' => true,
            'format' => 'onsite',
            'capacity' => 1,
            'location' => null,
            'meetingUrl' => null,
            'status' => 'scheduled',
        ], $payload ?? []);
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
        $startsAt = DateTimeParser::fromString(is_string($payload['startsAt'] ?? null) ? $payload['startsAt'] : 'now');
        $fallbackEndsAt = $startsAt?->modify('+1 day')?->format(\DateTimeInterface::ATOM);
        $endsAt = DateTimeParser::fromString(is_string($payload['endsAt'] ?? null) ? $payload['endsAt'] : $fallbackEndsAt);
        $dailyStart = DateTimeParser::fromString(is_string($payload['dailyStartTime'] ?? null) ? $payload['dailyStartTime'] : '08:00');
        $dailyEnd = DateTimeParser::fromString(is_string($payload['dailyEndTime'] ?? null) ? $payload['dailyEndTime'] : '20:00');

        if (
            !$startsAt instanceof \DateTimeImmutable
            || !$endsAt instanceof \DateTimeImmutable
            || !$dailyStart instanceof \DateTimeImmutable
            || !$dailyEnd instanceof \DateTimeImmutable
        ) {
            throw new \InvalidArgumentException('Dates de session invalides.');
        }

        return new self([
            'trainingId' => is_numeric($payload['trainingId'] ?? null) ? (int) $payload['trainingId'] : 0,
            'startsAt' => $startsAt,
            'endsAt' => $endsAt,
            'dailyStartTime' => $dailyStart,
            'dailyEndTime' => $dailyEnd,
            'includeWeekends' => is_bool($payload['includeWeekends'] ?? null) ? $payload['includeWeekends'] : true,
            'format' => in_array($payload['format'] ?? null, ['onsite', 'remote'], true) ? $payload['format'] : 'onsite',
            'capacity' => max(1, is_numeric($payload['capacity'] ?? null) ? (int) $payload['capacity'] : 1),
            'location' => is_string($payload['location'] ?? null) ? trim($payload['location']) : null,
            'meetingUrl' => is_string($payload['meetingUrl'] ?? null) ? trim($payload['meetingUrl']) : null,
            'status' => is_string($payload['status'] ?? null) && '' !== trim($payload['status']) ? trim($payload['status']) : 'scheduled',
        ]);
    }
}
