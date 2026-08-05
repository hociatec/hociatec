<?php

declare(strict_types=1);

namespace App\Module\User\Application\Port;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

interface ShippingAddressRepositoryPort
{
    public function save(ShippingAddress $address): void;

    public function delete(ShippingAddress $address): void;

    /** @return list<ShippingAddress> */
    public function findAllForUser(User $user): array;

    public function findOneForUser(int $id, User $user): ?ShippingAddress;

    public function findFirstForUser(User $user): ?ShippingAddress;

    public function findDefaultForUser(User $user): ?ShippingAddress;

    public function setDefault(User $user, ShippingAddress $address): void;
}
