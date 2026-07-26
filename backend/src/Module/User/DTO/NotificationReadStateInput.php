<?php

declare(strict_types=1);

namespace App\Module\User\DTO;

final readonly class NotificationReadStateInput
{
    /** @param list<string>|null $seenKeys */
    public function __construct(
        public ?array $seenKeys,
        public ?string $dismissedKey,
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

        return new self(
            $seenKeys,
            is_string($payload['dismissedKey'] ?? null) ? trim($payload['dismissedKey']) : null,
            is_string($payload['seenSignature'] ?? null) ? trim($payload['seenSignature']) : null,
        );
    }
}
