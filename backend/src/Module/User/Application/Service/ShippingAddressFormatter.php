<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Domain\Entity\ShippingAddress;

final class ShippingAddressFormatter
{
    private function __construct()
    {
    }

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
            'company' => $address->getCompany(),
            'companySiren' => $address->getCompanySiren(),
            'companyVatNumber' => $address->getCompanyVatNumber(),
            'purchaseOrderNumber' => $address->getPurchaseOrderNumber(),
            'isDefault' => $address->isDefault(),
        ];
    }
}
