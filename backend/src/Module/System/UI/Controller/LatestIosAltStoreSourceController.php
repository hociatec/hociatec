<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/public/ios/source', name: 'api_public_ios_source', methods: ['GET'])]
final readonly class LatestIosAltStoreSourceController
{
    private const SOURCE_URL = 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json';

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $upstream = $this->httpClient->request('GET', self::SOURCE_URL, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 30,
                'max_duration' => 45,
            ]);

            $content = $upstream->getContent();
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
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
