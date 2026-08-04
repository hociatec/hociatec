<?php

declare(strict_types=1);

namespace App\Module\Training\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TrainingInput
{
    /**
     * @param list<string> $availableFormats
     * @param list<string> $roadmap
     */
    public function __construct(
        #[Assert\NotBlank] public string $title,
        public ?string $slug,
        public ?string $shortDescription,
        public ?string $objective,
        public ?string $audience,
        public ?string $category,
        #[Assert\Positive] public int $durationMinutes,
        #[Assert\PositiveOrZero] public int $priceCents,
        public array $availableFormats,
        public array $roadmap,
        public bool $isActive,
    ) {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        $strings = static function (mixed $value): array {
            return is_array($value) ? array_values(array_filter(array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $value), static fn (string $item): bool => '' !== $item)) : [];
        };

        return new self(
            is_string($p['title'] ?? null) ? trim($p['title']) : '',
            is_string($p['slug'] ?? null) ? trim($p['slug']) : null,
            is_string($p['shortDescription'] ?? null) ? trim($p['shortDescription']) : null,
            is_string($p['objective'] ?? null) ? trim($p['objective']) : null,
            is_string($p['audience'] ?? null) ? trim($p['audience']) : null,
            is_string($p['category'] ?? null) ? trim($p['category']) : null,
            is_numeric($p['durationMinutes'] ?? null) ? (int) $p['durationMinutes'] : 60,
            is_numeric($p['priceCents'] ?? null) ? max(0, (int) $p['priceCents']) : 0,
            $strings($p['availableFormats'] ?? ['onsite']),
            $strings($p['roadmap'] ?? []),
            is_bool($p['isActive'] ?? null) ? $p['isActive'] : true,
        );
    }
}
