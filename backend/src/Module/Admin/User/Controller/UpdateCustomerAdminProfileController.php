<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/admin-profile', name: 'api_admin_customers_update_admin_profile', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateCustomerAdminProfileController extends AbstractController
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return ApiResponse::error('Client introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $payload = '' !== $request->getContent() ? $request->toArray() : [];
        } catch (\JsonException) {
            return ApiResponse::error('Payload JSON invalide.');
        }

        $notes = array_key_exists('adminNotes', $payload) ? (string) ($payload['adminNotes'] ?? '') : '';
        $tagsInput = $payload['adminTags'] ?? [];
        $tags = [];

        if (is_array($tagsInput)) {
            foreach ($tagsInput as $tag) {
                $tags[] = (string) $tag;
            }
        }

        $user
            ->setAdminNotes('' !== $notes ? $notes : null)
            ->setAdminTags($tags);

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
