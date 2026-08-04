<?php

declare(strict_types=1);

namespace App\Module\Notification\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Notification\Application\Service\CommunicationPreferences;
use App\Module\Notification\Application\Service\CommunicationPreferenceUpdater;
use App\Module\Notification\Domain\Exception\NotificationOperationException;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/communication-preferences')]
#[IsGranted('ROLE_USER')]
final class CommunicationPreferencesController extends AbstractController
{
    public function __construct(private readonly CommunicationPreferenceUpdater $updater)
    {
    }

    #[Route('', name: 'api_auth_communication_preferences_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return ApiResponse::success($this->payload($this->currentUser()));
    }

    #[Route('', name: 'api_auth_communication_preferences_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $payload = \App\Infrastructure\Http\JsonRequestInput::payload($request);
        $user = $this->currentUser();

        try {
            $this->updater->update($user, is_array($payload['preferences'] ?? null) ? $payload['preferences'] : []);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NotificationOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success($this->payload($user), JsonResponse::HTTP_OK, 'Préférences enregistrées.');
    }

    /** @return array{preferences:list<string>,choices:list<array{value:string,label:string,description:string}>} */
    private function payload(User $user): array
    {
        return [
            'preferences' => $user->getCommunicationPreferences(),
            'choices' => CommunicationPreferences::choices(),
        ];
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
