<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller\Address;

use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses/me', name: 'api_addresses_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyAddressesController extends AbstractController
{
    public function __construct(private readonly ShippingAddressRepositoryPort $addresses)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();
        $items = array_map(
            fn ($a) => ShippingAddressFormatter::toArray($a),
            $this->addresses->findAllForUser($user, $pagination->perPage, $pagination->offset())
        );

        return ApiResponse::paginated($items, $pagination->metadata($this->addresses->countForUser($user)));
    }
}
