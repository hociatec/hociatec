<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
class ProfileController extends AbstractController
{
    public function __construct(private readonly ShippingAddressRepository $addresses)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::success([
                'authenticated' => false,
            ]);
        }

        $default = $this->addresses->findDefaultForUser($user) ?? $this->addresses->findFirstForUser($user);

        return ApiResponse::success([
            'authenticated' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'address' => $default?->getAddress(),
            'postalCode' => $default?->getPostalCode(),
            'city' => $default?->getCity(),
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'phoneNumber' => $user->getPhoneNumber(),
            'gender' => $user->getGender(),
            'communicationPreferences' => $user->getCommunicationPreferences(),
        ]);
    }
}
