<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Voucher\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class VoucherInput
{
    #[Assert\NotBlank]
    public string $name;
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    public string $code;
    #[Assert\Length(max: 2000)]
    public ?string $description;
    #[Assert\NotBlank]
    public string $discountType;
    #[Assert\Positive]
    public int $discountValue;
    public bool $isActive;
    public ?string $startsAt;
    public ?string $endsAt;

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
        $this->name = (string) $data['name'];
        $this->code = (string) $data['code'];
        $this->description = $data['description'];
        $this->discountType = (string) $data['discountType'];
        $this->discountValue = (int) $data['discountValue'];
        $this->isActive = (bool) $data['isActive'];
        $this->startsAt = $data['startsAt'];
        $this->endsAt = $data['endsAt'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['name'] ?? null) ? trim($payload['name']) : '', is_string($payload['code'] ?? null) ? mb_strtoupper(trim($payload['code'])) : '', is_string($payload['description'] ?? null) ? trim($payload['description']) : null, is_string($payload['discountType'] ?? null) ? trim($payload['discountType']) : '', is_numeric($payload['discountValue'] ?? null) ? (int) $payload['discountValue'] : 0, is_bool($payload['isActive'] ?? null) ? $payload['isActive'] : true, is_string($payload['startsAt'] ?? null) ? trim($payload['startsAt']) : null, is_string($payload['endsAt'] ?? null) ? trim($payload['endsAt']) : null);
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['name', 'code', 'description', 'discountType', 'discountValue', 'isActive', 'startsAt', 'endsAt'];
        $defaults = array_fill_keys($keys, null);
        $defaults['name'] = '';
        $defaults['code'] = '';
        $defaults['discountType'] = '';
        $defaults['discountValue'] = 0;
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
