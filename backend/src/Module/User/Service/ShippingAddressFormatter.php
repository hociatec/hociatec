<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\ShippingAddress;

final class ShippingAddressFormatter
{
    private function __construct() {}

    /**
     * @return array<string, mixed>
     */
    public static function toArray(ShippingAddress $address): array
    {
        return [
            'id' => $address->getId(),
            'name' => $address->getName(),
            'address' => $address->getAddress(),
            'postalCode' => $address->getPostalCode(),
            'city' => $address->getCity(),
            'isDefault' => $address->isDefault(),
        ];
    }
}
