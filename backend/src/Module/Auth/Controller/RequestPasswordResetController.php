<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Module\Auth\Service\PasswordResetService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/password-reset/request', name: 'api_auth_password_reset_request', methods: ['POST'])]
class RequestPasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        #[Autowire(service: 'limiter.password_reset_request')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $email = trim((string) ($payload['email'] ?? ''));
        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error(
                'Veuillez saisir une adresse e-mail valide.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $limit = $this->limiter
            ->create($request->getClientIp() ?? 'unknown')
            ->consume(1);

        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de demandes de réinitialisation. Veuillez réessayer plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $this->passwordResetService->request($email);

        return ApiResponse::success([
            'message' => 'Si un compte correspond à cette adresse e-mail, un lien de réinitialisation vient d’être envoyé.',
        ]);
    }
}
