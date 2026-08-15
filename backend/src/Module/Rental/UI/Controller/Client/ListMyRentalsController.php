<?php

declare(strict_types=1);

namespace App\Module\Rental\UI\Controller\Client;

use App\Module\Rental\Application\Workflow\CustomerRentalPortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/rentals/me', name: 'api_rentals_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListMyRentalsController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(private readonly CustomerRentalPortalService $portal)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $result = $this->portal->listForUser($this->currentUser(), $pagination->perPage, $pagination->offset());

        return ApiResponse::success([
            'upcoming' => $result['upcoming'],
            'past' => $result['past'],
            'meta' => [
                'page' => $pagination->page,
                'perPage' => $pagination->perPage,
                'upcomingTotal' => $result['upcomingTotal'],
                'pastTotal' => $result['pastTotal'],
            ],
        ]);
    }
}
