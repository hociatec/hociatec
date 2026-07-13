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

    private function __construct() {}

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

        return $self;
    }
}

