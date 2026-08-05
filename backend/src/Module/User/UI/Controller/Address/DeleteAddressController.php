<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller\Address;

use App\Module\User\Application\Writer\ShippingAddressWriter;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses/{id}', name: 'api_addresses_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class DeleteAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressRepositoryPort $addresses,
        private readonly ShippingAddressWriter $writer,
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

        $this->writer->delete($address);

        return ApiResponse::successItem('message', 'Adresse supprimée');
    }
}
