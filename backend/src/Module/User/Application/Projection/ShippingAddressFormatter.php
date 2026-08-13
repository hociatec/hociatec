<?php

declare(strict_types=1);

namespace App\Module\User\Application\Projection;

use App\Module\User\Domain\Entity\ShippingAddress;

final class ShippingAddressFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(ShippingAddress $address): array
    {
        return [
            'id' => $address->getId(),
            'type' => $address->getType(),
            'name' => $address->getName(),
            'address' => $address->getAddress(),
            'addressComplement' => $address->getAddressComplement(),
            'postalCode' => $address->getPostalCode(),
            'city' => $address->getCity(),
            'company' => $address->getCompany(),
            'companySiren' => $address->getCompanySiren(),
            'companyVatNumber' => $address->getCompanyVatNumber(),
            'isDefault' => $address->isDefault(),
        ];
    }
}
