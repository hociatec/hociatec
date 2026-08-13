<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Module\System\Application\Provider\LatestIosAltStoreSourceProvider;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/ios/source', name: 'api_public_ios_source', methods: ['GET'])]
final readonly class LatestIosAltStoreSourceController
{
    public function __construct(
        private LatestIosAltStoreSourceProvider $source,
    ) {
    }

    public function __invoke(): Response
    {
        $content = $this->source->fetchContent();
        if (null === $content) {
            return ApiResponse::error('Source AltStore iPhone indisponible.', Response::HTTP_BAD_GATEWAY);
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
