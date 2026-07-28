<?php

declare(strict_types=1);

namespace App\Module\Notification\DTO;

final readonly class NotificationReadStateInput
{
    /**
     * @param list<string>|null $seenKeys
     * @param list<string>|null $dismissedKeys
     */
    public function __construct(
        public ?array $seenKeys,
        public ?string $dismissedKey,
        public ?array $dismissedKeys,
        public ?string $seenSignature,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $seenKeys = null;
        if (is_array($payload['seenKeys'] ?? null)) {
            $seenKeys = array_values(array_filter(
                array_map(static fn (mixed $key): string => is_string($key) ? trim($key) : '', $payload['seenKeys']),
                static fn (string $key): bool => '' !== $key,
            ));
        }

        $dismissedKeys = null;
        if (is_array($payload['dismissedKeys'] ?? null)) {
            $dismissedKeys = array_values(array_filter(
                array_map(static fn (mixed $key): string => is_string($key) ? trim($key) : '', $payload['dismissedKeys']),
                static fn (string $key): bool => '' !== $key,
            ));
        }

        return new self(
            $seenKeys,
            is_string($payload['dismissedKey'] ?? null) ? trim($payload['dismissedKey']) : null,
            $dismissedKeys,
            is_string($payload['seenSignature'] ?? null) ? trim($payload['seenSignature']) : null,
        );
    }
}
