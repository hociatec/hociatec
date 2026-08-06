<?php

declare(strict_types=1);

namespace App\Module\Training\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TrainingInput
{
    #[Assert\NotBlank]
    public string $title;
    public ?string $slug;
    public ?string $shortDescription;
    public ?string $objective;
    public ?string $audience;
    public ?string $category;
    #[Assert\Positive]
    public int $durationMinutes;
    #[Assert\PositiveOrZero]
    public int $priceCents;
    /** @var list<string> */
    public array $availableFormats;
    /** @var list<string> */
    public array $roadmap;
    public bool $isActive;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->title = (string) $data['title'];
        $this->slug = $data['slug'];
        $this->shortDescription = $data['shortDescription'];
        $this->objective = $data['objective'];
        $this->audience = $data['audience'];
        $this->category = $data['category'];
        $this->durationMinutes = (int) $data['durationMinutes'];
        $this->priceCents = (int) $data['priceCents'];
        $this->availableFormats = $data['availableFormats'];
        $this->roadmap = $data['roadmap'];
        $this->isActive = (bool) $data['isActive'];
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

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['title', 'slug', 'shortDescription', 'objective', 'audience', 'category', 'durationMinutes', 'priceCents', 'availableFormats', 'roadmap', 'isActive'];
        $defaults = array_fill_keys($keys, null);
        $defaults['title'] = '';
        $defaults['durationMinutes'] = 60;
        $defaults['priceCents'] = 0;
        $defaults['availableFormats'] = [];
        $defaults['roadmap'] = [];
        $defaults['isActive'] = true;
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
