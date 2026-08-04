<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller\Address;

use App\Infrastructure\Http\ApiResponse;
use App\Module\User\Application\Service\DeleteShippingAddressService;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses/{id}', name: 'api_addresses_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class DeleteAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressRepository $addresses,
        private readonly DeleteShippingAddressService $deleter,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $address = $this->addresses->findOneForUser($id, $user);
        if (null === $address) {
            return ApiResponse::error('Adresse introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $this->deleter->delete($address);

        return ApiResponse::success(['message' => 'Adresse supprimée']);
    }
}
