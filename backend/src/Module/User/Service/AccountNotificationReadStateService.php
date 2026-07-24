<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AccountNotificationReadStateService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>, seenSignature: string}
     */
    public function read(User $user): array
    {
        return $this->format($this->decode($user));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>, seenSignature: string}
     */
    public function update(User $user, array $payload): array
    {
        $state = $this->decode($user);
        $seenKeys = $payload['seenKeys'] ?? null;
        $dismissedKey = $payload['dismissedKey'] ?? null;

        if (is_array($seenKeys)) {
            $state['seenKeys'] = $this->merge($state['seenKeys'], $seenKeys);
        }

        if (is_string($dismissedKey) && '' !== trim($dismissedKey)) {
            $state['dismissedKeys'] = $this->merge($state['dismissedKeys'], [$dismissedKey]);
            $state['seenKeys'] = $this->merge($state['seenKeys'], [$dismissedKey]);
        }

        if (null === $seenKeys && null === $dismissedKey) {
            $seenSignature = $payload['seenSignature'] ?? '';
            if (!is_string($seenSignature)) {
                throw new \InvalidArgumentException('État de lecture invalide.');
            }

            $state['seenKeys'] = $this->merge($state['seenKeys'], preg_split('/\R+/', $seenSignature) ?: []);
        }

        $formatted = $this->format($state);
        $user->setAccountNotificationsSeenSignature(json_encode($formatted, JSON_THROW_ON_ERROR));
        $this->entityManager->flush();

        return $formatted;
    }

    /**
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>}
     */
    private function decode(User $user): array
    {
        $rawState = $user->getAccountNotificationsSeenSignature() ?? '';
        if ('' === $rawState) {
            return ['seenKeys' => [], 'dismissedKeys' => []];
        }

        try {
            $decoded = json_decode($rawState, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [
                'seenKeys' => $this->normalize(preg_split('/\R+/', $rawState) ?: []),
                'dismissedKeys' => [],
            ];
        }

        if (!is_array($decoded)) {
            return ['seenKeys' => [], 'dismissedKeys' => []];
        }

        return [
            'seenKeys' => $this->normalize($decoded['seenKeys'] ?? []),
            'dismissedKeys' => $this->normalize($decoded['dismissedKeys'] ?? []),
        ];
    }

    /**
     * @param array{seenKeys: list<string>, dismissedKeys: list<string>} $state
     *
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>, seenSignature: string}
     */
    private function format(array $state): array
    {
        $seenKeys = $this->normalize($state['seenKeys']);
        $dismissedKeys = $this->normalize($state['dismissedKeys']);

        return [
            'seenKeys' => $seenKeys,
            'dismissedKeys' => $dismissedKeys,
            'seenSignature' => implode("\n", $seenKeys),
        ];
    }

    /**
     * @return list<string>
     */
    private function normalize(mixed $keys): array
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
            if ('' !== $key && strlen($key) <= 255) {
                $normalized[] = $key;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<string> $existingKeys
     *
     * @return list<string>
     */
    private function merge(array $existingKeys, mixed $newKeys): array
    {
        return $this->normalize([...$existingKeys, ...$this->normalize($newKeys)]);
    }
}
