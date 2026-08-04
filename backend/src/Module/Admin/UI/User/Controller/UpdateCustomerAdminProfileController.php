<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\User\DTO\CustomerAdminProfileInput;
use App\Module\Admin\Application\User\Service\UpdateCustomerAdminProfileHandler;
use App\Module\User\Infrastructure\Repository\UserRepository;
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
        private readonly UserRepository $users,
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
            $payload = \App\Infrastructure\Http\JsonRequestInput::optionalPayload($request);
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
