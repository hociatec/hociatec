<?php

declare(strict_types=1);

namespace App\Module\Notification\Controller;

use App\Module\Notification\DTO\NotificationReadStateInput;
use App\Module\User\Entity\User;
use App\Module\Notification\Service\AccountNotificationReadStateService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/account-notifications/me/read-state')]
#[IsGranted('ROLE_USER')]
final class AccountNotificationsReadStateController extends AbstractController
{
    public function __construct(private readonly AccountNotificationReadStateService $readState)
    {
    }

    #[Route('', name: 'api_account_notifications_read_state_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return ApiResponse::success([
            'readState' => $this->readState->read($this->currentUser()),
        ]);
    }

    #[Route('', name: 'api_account_notifications_read_state_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
            $state = $this->readState->update($this->currentUser(), NotificationReadStateInput::fromArray($payload));
        } catch (\JsonException|\InvalidArgumentException) {
            return ApiResponse::error('État de lecture invalide.', Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return ApiResponse::error('Requête invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['readState' => $state]);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
