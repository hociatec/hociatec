<?php

declare(strict_types=1);

namespace App\Module\User\Application\Writer;

use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class ShippingAddressWriter
{
    public function __construct(
        private ShippingAddressRepositoryPort $addresses,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function save(ShippingAddress $address): void
    {
        $this->addresses->save($address);
        $this->unitOfWork->flush();
    }

    public function saveWithDefaultPolicy(User $user, ShippingAddress $address, bool $isDefault): void
    {
        $this->addresses->save($address);

        if ($isDefault || null === $this->addresses->findDefaultForUser($user)) {
            $this->addresses->setDefault($user, $address);
        }

        $this->unitOfWork->flush();
    }

    public function delete(ShippingAddress $address): void
    {
        $this->addresses->delete($address);
        $this->unitOfWork->flush();
    }

    public function setDefault(User $user, ShippingAddress $address): void
    {
        $this->addresses->setDefault($user, $address);
        $this->unitOfWork->flush();
    }
}
