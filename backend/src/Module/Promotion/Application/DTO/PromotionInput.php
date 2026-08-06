<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\DTO;

use App\Module\Promotion\Domain\Entity\Promotion;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PromotionInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 140)]
    public string $name;
    #[Assert\NotBlank]
    #[Assert\Length(max: 140)]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    public string $slug;
    #[Assert\Choice(choices: [Promotion::TYPE_PERCENT, Promotion::TYPE_FIXED_CENTS])]
    public string $discountType;
    #[Assert\Positive]
    public int $discountValue;
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    public string $audienceKey;
    /** @var array<string,mixed> */
    public array $criteria;
    #[Assert\Length(max: 100)]
    public ?string $description;
    public bool $isActive;
    public ?\DateTimeImmutable $startsAt;
    public ?\DateTimeImmutable $endsAt;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->name = (string) $data['name'];
        $this->slug = (string) $data['slug'];
        $this->discountType = (string) $data['discountType'];
        $this->discountValue = (int) $data['discountValue'];
        $this->audienceKey = (string) $data['audienceKey'];
        $this->criteria = $data['criteria'];
        $this->description = $data['description'];
        $this->isActive = (bool) $data['isActive'];
        $this->startsAt = $data['startsAt'];
        $this->endsAt = $data['endsAt'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $criteria = $payload['criteria'] ?? [];

        return new self(
            trim(is_string($payload['name'] ?? null) ? $payload['name'] : ''),
            trim(is_string($payload['slug'] ?? null) ? $payload['slug'] : ''),
            trim(is_string($payload['discountType'] ?? null) ? $payload['discountType'] : ''),
            is_numeric($payload['discountValue'] ?? null) ? (int) $payload['discountValue'] : 0,
            trim(is_string($payload['audienceKey'] ?? null) ? $payload['audienceKey'] : ''),
            is_array($criteria) ? $criteria : [],
            isset($payload['description']) && is_string($payload['description']) ? trim($payload['description']) : null,
            (bool) ($payload['isActive'] ?? true),
            self::date($payload['startsAt'] ?? null),
            self::date($payload['endsAt'] ?? null),
        );
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }

    /**
     * @param array<int|string, mixed> $values
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['name', 'slug', 'discountType', 'discountValue', 'audienceKey', 'criteria', 'description', 'isActive', 'startsAt', 'endsAt'];
        $defaults = array_fill_keys($keys, null);
        $defaults['name'] = '';
        $defaults['slug'] = '';
        $defaults['discountType'] = '';
        $defaults['discountValue'] = 0;
        $defaults['audienceKey'] = '';
        $defaults['criteria'] = [];
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
