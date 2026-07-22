<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'api_account_notifications_read_state_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::success([
            'readState' => $this->formatState($this->readState($user)),
        ]);
    }

    #[Route('', name: 'api_account_notifications_read_state_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Requête invalide.', Response::HTTP_BAD_REQUEST);
        }

        $state = $this->readState($user);

        $seenKeys = $payload['seenKeys'] ?? null;
        if (is_array($seenKeys)) {
            $state['seenKeys'] = $this->mergeKeys($state['seenKeys'], $seenKeys);
        }

        $dismissedKey = $payload['dismissedKey'] ?? null;
        if (is_string($dismissedKey) && trim($dismissedKey) !== '') {
            $state['dismissedKeys'] = $this->mergeKeys($state['dismissedKeys'], [$dismissedKey]);
            $state['seenKeys'] = $this->mergeKeys($state['seenKeys'], [$dismissedKey]);
        }

        if ($seenKeys === null && $dismissedKey === null) {
            $seenSignature = $payload['seenSignature'] ?? '';
            if (!is_string($seenSignature)) {
                return ApiResponse::error('État de lecture invalide.', Response::HTTP_BAD_REQUEST);
            }

            $state['seenKeys'] = $this->mergeKeys($state['seenKeys'], preg_split('/\R+/', $seenSignature) ?: []);
        }

        $user->setAccountNotificationsSeenSignature(json_encode($this->formatState($state), JSON_THROW_ON_ERROR));
        $this->em->flush();

        return ApiResponse::success([
            'readState' => $this->formatState($state),
        ]);
    }

    /**
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>}
     */
    private function readState(User $user): array
    {
        $rawState = $user->getAccountNotificationsSeenSignature() ?? '';
        if ($rawState === '') {
            return ['seenKeys' => [], 'dismissedKeys' => []];
        }

        try {
            $decoded = json_decode($rawState, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [
                'seenKeys' => $this->normalizeKeys(preg_split('/\R+/', $rawState) ?: []),
                'dismissedKeys' => [],
            ];
        }

        if (!is_array($decoded)) {
            return ['seenKeys' => [], 'dismissedKeys' => []];
        }

        return [
            'seenKeys' => $this->normalizeKeys($decoded['seenKeys'] ?? []),
            'dismissedKeys' => $this->normalizeKeys($decoded['dismissedKeys'] ?? []),
        ];
    }

    /**
     * @param array{seenKeys: list<string>, dismissedKeys: list<string>} $state
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>, seenSignature: string}
     */
    private function formatState(array $state): array
    {
        $seenKeys = $this->normalizeKeys($state['seenKeys']);
        $dismissedKeys = $this->normalizeKeys($state['dismissedKeys']);

        return [
            'seenKeys' => $seenKeys,
            'dismissedKeys' => $dismissedKeys,
            'seenSignature' => implode("\n", $seenKeys),
        ];
    }

    /**
     * @param mixed $keys
     * @return list<string>
     */
    private function normalizeKeys(mixed $keys): array
    {
        if (!is_array($keys)) {
            return [];
        }

        $normalized = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $key = trim($key);
            if ($key === '' || strlen($key) > 255) {
                continue;
            }

            $normalized[] = $key;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<string> $existingKeys
     * @param mixed $newKeys
     * @return list<string>
     */
    private function mergeKeys(array $existingKeys, mixed $newKeys): array
    {
        return $this->normalizeKeys([...$existingKeys, ...$this->normalizeKeys($newKeys)]);
    }
}
