<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;

final readonly class UserProfileFormatter
{
    public function __construct(private ShippingAddressRepository $addresses)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function format(User $user): array
    {
        $address = $this->addresses->findDefaultForUser($user) ?? $this->addresses->findFirstForUser($user);

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'address' => $address?->getAddress(),
            'postalCode' => $address?->getPostalCode(),
            'city' => $address?->getCity(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'phoneNumber' => $user->getPhoneNumber(),
            'gender' => $user->getGender(),
        ];
    }
}
