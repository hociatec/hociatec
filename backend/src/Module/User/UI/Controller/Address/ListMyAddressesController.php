<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller\Address;

use App\Module\User\Application\Workflow\CustomerAddressBookService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
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
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerAddressBookService $addressBook,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $result = $this->addressBook->listForUser($this->currentUser(), $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated($result['items'], $pagination->metadata($result['total']));
    }
}
