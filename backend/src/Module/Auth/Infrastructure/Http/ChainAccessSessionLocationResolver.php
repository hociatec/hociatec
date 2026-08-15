<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

final readonly class ChainAccessSessionLocationResolver implements AccessSessionLocationResolver
{
    /**
     * @param iterable<AccessSessionLocationResolver> $resolvers
     */
    public function __construct(private iterable $resolvers = [])
    {
    }

    public function resolve(Request $request, ?string $clientIp): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $location = $resolver->resolve($request, $clientIp);
            if (null !== $location) {
                return $location;
            }
        }

        return null;
    }
}
