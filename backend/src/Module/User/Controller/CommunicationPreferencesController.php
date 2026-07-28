<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\Entity\User;
use App\Module\User\Service\CommunicationPreferences;
use App\Module\User\Service\UserPersistence;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/communication-preferences')]
#[IsGranted('ROLE_USER')]
final class CommunicationPreferencesController extends AbstractController
{
    public function __construct(private readonly UserPersistence $persistence)
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
        $payload = JsonPayload::decode($request);
        $preferences = CommunicationPreferences::normalize($payload['preferences'] ?? []);

        if ([] === $preferences) {
            return ApiResponse::error('Sélectionnez au moins un moyen de communication.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->currentUser();
        $user->setCommunicationPreferences($preferences);
        $this->persistence->flush();

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
