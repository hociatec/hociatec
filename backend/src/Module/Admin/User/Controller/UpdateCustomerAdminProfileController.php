<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\Admin\User\DTO\CustomerAdminProfileInput;
use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/admin-profile', name: 'api_admin_customers_update_admin_profile', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateCustomerAdminProfileController extends AbstractController
{
    public function __construct(private readonly UserRepository $users, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return ApiResponse::error('Client introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $payload = '' !== $request->getContent() ? \App\Shared\Http\JsonPayload::decode($request) : [];
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.');
        }

        $input = CustomerAdminProfileInput::fromArray($payload);
        $this->validator->validate($input);

        $user
            ->setAdminNotes('' !== $input->adminNotes ? $input->adminNotes : null)
            ->setAdminTags($input->adminTags);

        $this->users->save($user, true);

        return ApiResponse::success([
            'customer' => [
                'id' => $user->getId(),
                'adminNotes' => $user->getAdminNotes(),
                'adminTags' => $user->getAdminTags(),
            ],
        ]);
    }
}
