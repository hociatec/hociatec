<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Module\User\Repository\ShippingAddressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(private readonly ShippingAddressRepository $addresses) {}

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $default = $this->addresses->findDefaultForUser($user) ?? $this->addresses->findFirstForUser($user);

        return ApiResponse::success([
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
        ]);
    }
}
