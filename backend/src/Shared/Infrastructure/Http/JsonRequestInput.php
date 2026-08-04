<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final class JsonRequestInput
{
    private function __construct()
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $inputClass
     *
     * @return T
     */
    public static function decode(Request $request, string $inputClass): object
    {
        if (!method_exists($inputClass, 'fromArray')) {
            throw new \InvalidArgumentException(sprintf('Input "%s" must expose fromArray().', $inputClass));
        }

        $input = $inputClass::fromArray(JsonPayload::decode($request));
        if (!$input instanceof $inputClass) {
            throw new \InvalidArgumentException(sprintf('Input "%s" did not return an instance of itself.', $inputClass));
        }

        return $input;
    }

    /** @return array<string, mixed> */
    public static function payload(Request $request): array
    {
        return JsonPayload::decode($request);
    }

    /** @return array<string, mixed> */
    public static function optionalPayload(Request $request): array
    {
        return '' !== $request->getContent() ? JsonPayload::decode($request) : [];
    }
}
