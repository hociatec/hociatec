<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateBetaCampaignInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $name,
        #[Assert\NotBlank]
        public string $description,
        #[Assert\Choice(choices: ['draft', 'active', 'closed'])]
        public string $status,
        public ?\DateTimeImmutable $startsAt,
        public ?\DateTimeImmutable $endsAt,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::string($payload, 'name'),
            self::string($payload, 'description'),
            self::status($payload['status'] ?? 'draft'),
            self::date($payload['startsAt'] ?? null),
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

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
