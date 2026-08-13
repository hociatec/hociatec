<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Service;

use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class ShippingAddressFormatterTest extends TestCase
{
    public function testToArrayExposesAllAddressFields(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $address = new ShippingAddress($user, 'Ada Lovelace', '1 rue de Paris', '75001', 'Paris');
        $address
            ->setType(ShippingAddress::TYPE_PROFESSIONAL)
            ->setAddressComplement('Bâtiment B')
            ->setCompany('Hociatec')
            ->setCompanySiren('123456789')
            ->setCompanyVatNumber('FR123456789')
            ->setIsDefault(true);
        $this->setId($address, 33);

        self::assertSame([
            'id' => 33,
            'type' => 'professional',
            'name' => 'Ada Lovelace',
            'address' => '1 rue de Paris',
            'addressComplement' => 'Bâtiment B',
            'postalCode' => '75001',
            'city' => 'Paris',
            'company' => 'Hociatec',
            'companySiren' => '123456789',
            'companyVatNumber' => 'FR123456789',
            'isDefault' => true,
        ], (new ShippingAddressFormatter())->toArray($address));
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
