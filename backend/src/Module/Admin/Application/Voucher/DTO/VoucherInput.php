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

    /**
     * @param array{
     *   name:string,
     *   code:string,
     *   description:?string,
     *   discountType:string,
     *   discountValue:int,
     *   isActive:bool,
     *   startsAt:?string,
     *   endsAt:?string
     * } $payload
     */
    public function __construct(
        ?array $payload = null,
    ) {
        $payload ??= [
            'name' => '',
            'code' => '',
            'description' => null,
            'discountType' => '',
            'discountValue' => 0,
            'isActive' => true,
            'startsAt' => null,
            'endsAt' => null,
        ];
        $this->name = $payload['name'];
        $this->code = $payload['code'];
        $this->description = $payload['description'];
        $this->discountType = $payload['discountType'];
        $this->discountValue = $payload['discountValue'];
        $this->isActive = $payload['isActive'];
        $this->startsAt = $payload['startsAt'];
        $this->endsAt = $payload['endsAt'];
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            [
                'name' => is_string($payload['name'] ?? null) ? trim($payload['name']) : '',
                'code' => is_string($payload['code'] ?? null) ? mb_strtoupper(trim($payload['code'])) : '',
                'description' => is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
                'discountType' => is_string($payload['discountType'] ?? null) ? trim($payload['discountType']) : '',
                'discountValue' => is_numeric($payload['discountValue'] ?? null) ? (int) $payload['discountValue'] : 0,
                'isActive' => is_bool($payload['isActive'] ?? null) ? $payload['isActive'] : true,
                'startsAt' => is_string($payload['startsAt'] ?? null) ? trim($payload['startsAt']) : null,
                'endsAt' => is_string($payload['endsAt'] ?? null) ? trim($payload['endsAt']) : null,
            ],
        );
    }
}
