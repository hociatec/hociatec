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

    /**
     * @param array{
     *   name:string,
     *   slug:string,
     *   discountType:string,
     *   discountValue:int,
     *   audienceKey:string,
     *   criteria:array<string,mixed>,
     *   description:?string,
     *   isActive:bool,
     *   startsAt:?DateTimeImmutable,
     *   endsAt:?DateTimeImmutable
     * } $payload
     */
    public function __construct(
        ?array $payload = null,
    ) {
        $payload ??= [
            'name' => '',
            'slug' => '',
            'discountType' => '',
            'discountValue' => 0,
            'audienceKey' => '',
            'criteria' => [],
            'description' => null,
            'isActive' => true,
            'startsAt' => null,
            'endsAt' => null,
        ];
        $this->name = $payload['name'];
        $this->slug = $payload['slug'];
        $this->discountType = $payload['discountType'];
        $this->discountValue = $payload['discountValue'];
        $this->audienceKey = $payload['audienceKey'];
        $this->criteria = $payload['criteria'];
        $this->description = $payload['description'];
        $this->isActive = $payload['isActive'];
        $this->startsAt = $payload['startsAt'];
        $this->endsAt = $payload['endsAt'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $criteria = $payload['criteria'] ?? [];

        return new self(
            [
                'name' => trim(is_string($payload['name'] ?? null) ? $payload['name'] : ''),
                'slug' => trim(is_string($payload['slug'] ?? null) ? $payload['slug'] : ''),
                'discountType' => trim(is_string($payload['discountType'] ?? null) ? $payload['discountType'] : ''),
                'discountValue' => is_numeric($payload['discountValue'] ?? null) ? (int) $payload['discountValue'] : 0,
                'audienceKey' => trim(is_string($payload['audienceKey'] ?? null) ? $payload['audienceKey'] : ''),
                'criteria' => is_array($criteria) ? $criteria : [],
                'description' => isset($payload['description']) && is_string($payload['description']) ? trim($payload['description']) : null,
                'isActive' => (bool) ($payload['isActive'] ?? true),
                'startsAt' => self::date($payload['startsAt'] ?? null),
                'endsAt' => self::date($payload['endsAt'] ?? null),
            ],
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

}
