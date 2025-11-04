<?php

declare(strict_types=1);

namespace App\Module\User\Controller\Address;

use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses/{id}', name: 'api_addresses_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class DeleteAddressController extends AbstractController
{
    public function __construct(private readonly ShippingAddressRepository $addresses) {}

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $address = $this->addresses->findOneForUser($id, $user);
        if ($address === null) {
            return ApiResponse::error('Adresse introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $this->addresses->remove($address, true);
        return ApiResponse::success(['message' => 'Adresse supprimée']);
    }
}

