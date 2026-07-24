<?php

declare(strict_types=1);

namespace App\Module\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ShippingAddressInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    public string $name;

    #[Assert\NotBlank]
    public string $address;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public string $postalCode;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $city;

    #[Assert\Length(max: 180)]
    public ?string $company = null;

    #[Assert\Length(max: 20)]
    public ?string $companySiren = null;

    #[Assert\Length(max: 32)]
    public ?string $companyVatNumber = null;

    #[Assert\Length(max: 80)]
    public ?string $purchaseOrderNumber = null;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $self = new self();
        $self->name = (string) ($payload['name'] ?? '');
        $self->address = (string) ($payload['address'] ?? '');
        $self->postalCode = (string) ($payload['postalCode'] ?? '');
        $self->city = (string) ($payload['city'] ?? '');
        $self->company = self::nullableString($payload['company'] ?? null);
        $self->companySiren = self::nullableString($payload['companySiren'] ?? null);
        $self->companyVatNumber = self::nullableString($payload['companyVatNumber'] ?? null);
        $self->purchaseOrderNumber = self::nullableString($payload['purchaseOrderNumber'] ?? null);

        return $self;
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return '' !== $normalized ? $normalized : null;
    }
}
