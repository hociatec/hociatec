<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Rental\Controller;

use App\Module\Admin\Application\Rental\Workflow\AdminRentalManagementService;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/rentals/{id}', name: 'api_admin_rentals_show', methods: ['GET'])]
#[IsGranted('ROLE_ORDERS_MANAGER')]
final class ShowAdminRentalController extends AbstractController
{
    public function __construct(private readonly AdminRentalManagementService $rentals)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $rental = $this->rentals->show($id);

        return null === $rental
            ? ApiResponse::notFound('Location introuvable.')
            : ApiResponse::successItem('item', $rental);
    }
}
