<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Module\Admin\Application\User\DTO\CustomerAdminProfileInput;
use App\Module\Admin\Application\User\Handler\UpdateCustomerAdminProfileHandler;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/admin-profile', name: 'api_admin_customers_update_admin_profile', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateCustomerAdminProfileController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryPort $users,
        private readonly UpdateCustomerAdminProfileHandler $updateProfile,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return ApiResponse::error('Client introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::optionalPayload($request);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload JSON invalide.');
        }

        $input = CustomerAdminProfileInput::fromArray($payload);
        $this->validator->validate($input);

        $this->updateProfile->update($user, $input);

        return ApiResponse::success([
            'customer' => [
                'id' => $user->getId(),
                'adminNotes' => $user->getAdminNotes(),
                'adminTags' => $user->getAdminTags(),
            ],
        ]);
    }
}
