<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerAddressBookService
{
    public function __construct(
        private ShippingAddressRepositoryPort $addresses,
        private ShippingAddressFormatter $formatter,
    ) {
    }

    /**
     * @return array{items:list<array<string,mixed>>, total:int}
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $items = $this->addresses->findAllForUser($user, $limit, $offset);

        return [
            'items' => array_map(fn ($address): array => $this->formatter->toArray($address), $items),
            'total' => $this->addresses->countForUser($user),
        ];
    }

    public function findForUser(User $user, int $addressId): ?\App\Module\User\Domain\Entity\ShippingAddress
    {
        return $this->addresses->findOneForUser($addressId, $user);
    }
}
