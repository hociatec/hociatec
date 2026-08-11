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

    /**
     * @param array{
     *   title?: string,
     *   slug?: ?string,
     *   shortDescription?: ?string,
     *   objective?: ?string,
     *   audience?: ?string,
     *   category?: ?string,
     *   durationMinutes?: int,
     *   priceCents?: int,
     *   availableFormats?: list<string>,
     *   roadmap?: list<string>,
     *   isActive?: bool
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'title' => '',
            'slug' => null,
            'shortDescription' => null,
            'objective' => null,
            'audience' => null,
            'category' => null,
            'durationMinutes' => 60,
            'priceCents' => 0,
            'availableFormats' => [],
            'roadmap' => [],
            'isActive' => true,
        ], $payload ?? []);
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

        return new self([
            'title' => is_string($p['title'] ?? null) ? trim($p['title']) : '',
            'slug' => is_string($p['slug'] ?? null) ? trim($p['slug']) : null,
            'shortDescription' => is_string($p['shortDescription'] ?? null) ? trim($p['shortDescription']) : null,
            'objective' => is_string($p['objective'] ?? null) ? trim($p['objective']) : null,
            'audience' => is_string($p['audience'] ?? null) ? trim($p['audience']) : null,
            'category' => is_string($p['category'] ?? null) ? trim($p['category']) : null,
            'durationMinutes' => is_numeric($p['durationMinutes'] ?? null) ? (int) $p['durationMinutes'] : 60,
            'priceCents' => is_numeric($p['priceCents'] ?? null) ? max(0, (int) $p['priceCents']) : 0,
            'availableFormats' => $strings($p['availableFormats'] ?? ['onsite']),
            'roadmap' => $strings($p['roadmap'] ?? []),
            'isActive' => is_bool($p['isActive'] ?? null) ? $p['isActive'] : true,
        ]);
    }
}
