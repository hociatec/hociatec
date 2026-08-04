<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/csrf-token', name: 'api_csrf_token', methods: ['GET'])]
final class CsrfTokenController extends AbstractController
{
    public function __construct(private readonly CsrfTokenService $csrfTokenService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $response = ApiResponse::success();
        $token = $this->csrfTokenService->issue($response, $request);

        $payload = json_decode((string) $response->getContent(), true);
        if (is_array($payload)) {
            $payload['data']['token'] = $token;
            $response->setData($payload);
        }

        return $response;
    }
}
