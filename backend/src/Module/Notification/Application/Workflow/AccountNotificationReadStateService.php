<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Workflow;

use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Notification\Domain\Exception\NotificationOperationException;
use App\Module\User\Application\Port\UserPersistencePort;
use App\Module\User\Domain\Entity\User;

final readonly class AccountNotificationReadStateService
{
    public function __construct(private UserPersistencePort $persistence)
    {
    }

    /**
     * @return array{seenKeys: list<string>, dismissedKeys: list<string>, seenSignature: string}
     */
    public function read(User $user): array
    {
        return $this->format($this->decode($user));
    }

    /** @return array{seenKeys: list<string>, dismissedKeys: list<string>, seenSignature: string} */
    public function update(User $user, NotificationReadStateInput $input): array
    {
        $state = $this->decode($user);
        $seenKeys = $input->seenKeys;
        $dismissedKey = $input->dismissedKey;
        $dismissedKeys = $input->dismissedKeys;

        if (is_array($seenKeys)) {
            $state['seenKeys'] = $this->merge($state['seenKeys'], $seenKeys);
        }

        if (is_string($dismissedKey) && '' !== trim($dismissedKey)) {
            $state['dismissedKeys'] = $this->merge($state['dismissedKeys'], [$dismissedKey]);
            $state['seenKeys'] = $this->merge($state['seenKeys'], [$dismissedKey]);
        }

        if (is_array($dismissedKeys)) {
            $state['dismissedKeys'] = $this->merge($state['dismissedKeys'], $dismissedKeys);
            $state['seenKeys'] = $this->merge($state['seenKeys'], $dismissedKeys);
        }

        if (null === $seenKeys && null === $dismissedKey && null === $dismissedKeys) {
            $seenSignature = $input->seenSignature ?? '';

            $state['seenKeys'] = $this->merge($state['seenKeys'], preg_split('/\R+/', $seenSignature) ?: []);
        }

        $formatted = $this->format($state);
        try {
            $user->setAccountNotificationsSeenSignature(json_encode($formatted, JSON_THROW_ON_ERROR));
            $this->persistence->flush();
        } catch (\JsonException|\RuntimeException $exception) {
            throw NotificationOperationException::failed('Impossible de mettre à jour l’état de lecture des notifications.', $exception);
        }

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
        } catch (\JsonException) {
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
    /** @param iterable<mixed>|string|null $keys */
    private function normalize(iterable|string|null $keys): array
    {
        if (is_string($keys) || null === $keys) {
            $items = [];
        } else {
            $items = is_array($keys) ? $keys : iterator_to_array($keys, false);
        }

        $normalized = [];
        foreach ($items as $key) {
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
    /** @param list<string> $newKeys */
    private function merge(array $existingKeys, array $newKeys): array
    {
        return $this->normalize([...$existingKeys, ...$this->normalize($newKeys)]);
    }
}
