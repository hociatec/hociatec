<?php

declare(strict_types=1);

namespace App\Module\User\Controller\Address;

use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\User\Service\ShippingAddressFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses/me', name: 'api_addresses_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAddressesController extends AbstractController
{
    public function __construct(private readonly ShippingAddressRepository $addresses) {}

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $items = array_map(
            fn($a) => ShippingAddressFormatter::toArray($a),
            $this->addresses->findAllForUser($user)
        );

        return ApiResponse::success(['items' => $items]);
    }
}

