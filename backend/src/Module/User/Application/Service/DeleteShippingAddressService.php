<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;

final readonly class DeleteShippingAddressService
{
    public function __construct(private ShippingAddressRepository $addresses)
    {
    }

    public function delete(ShippingAddress $address): void
    {
        $this->addresses->remove($address, true);
    }
}
