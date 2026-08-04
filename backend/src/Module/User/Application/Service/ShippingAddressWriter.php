<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class ShippingAddressWriter
{
    public function __construct(
        private ShippingAddressRepository $addresses,
        private DoctrineUnitOfWork $unitOfWork,
    ) {
    }

    public function save(ShippingAddress $address): void
    {
        $this->addresses->save($address);
        $this->unitOfWork->commit();
    }

    public function saveWithDefaultPolicy(User $user, ShippingAddress $address, bool $isDefault): void
    {
        $this->addresses->save($address);

        if ($isDefault || null === $this->addresses->findDefaultForUser($user)) {
            $this->addresses->setDefault($user, $address);
        }

        $this->unitOfWork->commit();
    }

    public function delete(ShippingAddress $address): void
    {
        $this->addresses->delete($address);
        $this->unitOfWork->commit();
    }

    public function setDefault(User $user, ShippingAddress $address): void
    {
        $this->addresses->setDefault($user, $address);
        $this->unitOfWork->commit();
    }
}
