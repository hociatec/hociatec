<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Service;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use PHPUnit\Framework\TestCase;

final class ShippingAddressFormatterTest extends TestCase
{
    public function testToArrayExposesAllAddressFields(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $address = new ShippingAddress($user, 'Ada Lovelace', '1 rue de Paris', '75001', 'Paris');
        $address
            ->setCompany('Hociatec')
            ->setCompanySiren('123456789')
            ->setCompanyVatNumber('FR123456789')
            ->setPurchaseOrderNumber('PO-2026-01')
            ->setIsDefault(true);
        $this->setId($address, 33);

        self::assertSame([
            'id' => 33,
            'name' => 'Ada Lovelace',
            'address' => '1 rue de Paris',
            'postalCode' => '75001',
            'city' => 'Paris',
            'company' => 'Hociatec',
            'companySiren' => '123456789',
            'companyVatNumber' => 'FR123456789',
            'purchaseOrderNumber' => 'PO-2026-01',
            'isDefault' => true,
        ], (new ShippingAddressFormatter())->toArray($address));
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
