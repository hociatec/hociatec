<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Rental\Controller;

use App\Module\Admin\Application\Rental\DTO\AdminRentalActionInput;
use App\Module\Admin\Application\Rental\Workflow\AdminRentalManagementService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\JsonRequestInput;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/rentals/{id}', name: 'api_admin_rentals_update', methods: ['PATCH'])]
#[IsGranted('ROLE_ORDERS_MANAGER')]
final class UpdateAdminRentalController extends AbstractController
{
    public function __construct(
        private readonly AdminRentalManagementService $rentals,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $input = JsonRequestInput::decode($request, AdminRentalActionInput::class);
        $this->validator->validate($input);

        try {
            $result = $this->rentals->handleAction($id, $input->action);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::badRequest($exception->getMessage());
        } catch (\LogicException $exception) {
            return ApiResponse::conflict($exception->getMessage());
        }

        return null === $result
            ? ApiResponse::notFound('Location introuvable.')
            : ApiResponse::successItem('item', $result['rental'], message: $result['message']);
    }
}
