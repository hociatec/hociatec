<?php

declare(strict_types=1);

namespace App\Module\Notification\Controller;

use App\Module\User\Entity\User;
use App\Module\Notification\Service\AccountNotificationProvider;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/account-notifications/me', name: 'api_account_notifications_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListAccountNotificationsController extends AbstractController
{
    public function __construct(private readonly AccountNotificationProvider $notifications)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $pagination = Pagination::fromRequest($request, 30, 100);

        return ApiResponse::paginated(
            $this->notifications->provideForUser($user, $pagination->perPage, $pagination->offset()),
            $pagination->metadata($this->notifications->countForUser($user)),
        );
    }
}
