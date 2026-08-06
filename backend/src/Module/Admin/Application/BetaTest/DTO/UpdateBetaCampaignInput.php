<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\DTO;

use App\Shared\Infrastructure\DateTime\DateTimeParser;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateBetaCampaignInput
{
    public function __construct(
        #[Assert\Length(max: 150)]
        public ?string $name,
        public ?string $description,
        #[Assert\Choice(choices: ['draft', 'active', 'closed'])]
        public ?string $status,
        public bool $hasStartsAt,
        public ?\DateTimeImmutable $startsAt,
        public bool $hasEndsAt,
        public ?\DateTimeImmutable $endsAt,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            \array_key_exists('name', $payload) ? self::string($payload, 'name') : null,
            \array_key_exists('description', $payload) ? self::string($payload, 'description') : null,
            \array_key_exists('status', $payload) ? self::status($payload['status']) : null,
            \array_key_exists('startsAt', $payload),
            self::date($payload['startsAt'] ?? null),
            \array_key_exists('endsAt', $payload),
            self::date($payload['endsAt'] ?? null),
        );
    }

    /** @param array<string, mixed> $payload */
    private static function string(array $payload, string $key): string
    {
        return is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
    }

    private static function status(mixed $value): string
    {
        return \in_array($value, ['draft', 'active', 'closed'], true) ? (string) $value : 'draft';
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return DateTimeParser::fromFormat('!Y-m-d', trim($value));
    }
}
