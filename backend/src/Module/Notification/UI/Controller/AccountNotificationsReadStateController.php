<?php

declare(strict_types=1);

namespace App\Module\Notification\UI\Controller;

use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Notification\Application\Workflow\AccountNotificationReadStateService;
use App\Module\Notification\Domain\Exception\NotificationOperationException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
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
    use AuthenticatedDomainUserTrait;

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
        $user = $this->currentUser();

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
            $state = $this->readState->update($user, NotificationReadStateInput::fromArray($payload));
        } catch (InvalidJsonPayloadException|\InvalidArgumentException) {
            return ApiResponse::error('État de lecture invalide.', Response::HTTP_BAD_REQUEST);
        } catch (NotificationOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::successItem('readState', $state);
    }
}
