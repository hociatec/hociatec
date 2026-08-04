<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final class JsonPayload
{
    private const MAX_BYTES = 1_048_576;

    private function __construct()
    {
    }

    /** @return array<string,mixed> */
    public static function decode(Request $request): array
    {
        $content = $request->getContent();
        if (strlen($content) > self::MAX_BYTES) {
            throw new InvalidJsonPayloadException('Payload trop volumineux.');
        }
        if ('' === trim($content)) {
            return [];
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidJsonPayloadException('Payload JSON invalide.');
        }

        if (!is_array($payload) || array_is_list($payload)) {
            throw new InvalidJsonPayloadException('Le payload JSON doit être un objet.');
        }

        return $payload;
    }
}
