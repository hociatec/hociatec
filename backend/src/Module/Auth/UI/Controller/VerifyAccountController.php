<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimitKeyFactory;
use App\Module\User\Application\Service\AccountVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/verify/{token}', name: 'api_auth_verify', methods: ['GET'])]
class VerifyAccountController extends AbstractController
{
    public function __construct(
        private readonly AccountVerificationService $verification,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.activation_verify')]
        private readonly RateLimiterFactory $activationVerifyLimiter,
    ) {
    }

    public function __invoke(string $token, Request $request): JsonResponse
    {
        $limiter = $this->activationVerifyLimiter->create($this->rateLimitKeys->forRequest($request, $token));
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error('Trop de tentatives, réessayez plus tard.', JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        return match ($this->verification->verify($token)) {
            AccountVerificationService::ALREADY_VERIFIED => ApiResponse::success(['message' => 'Votre compte est déjà activé.']),
            AccountVerificationService::EXPIRED => ApiResponse::error('Le lien d\'activation a expiré.', JsonResponse::HTTP_BAD_REQUEST),
            AccountVerificationService::VERIFIED => ApiResponse::success(['message' => 'Votre compte a été activé avec succès.']),
            default => ApiResponse::error('Lien d\'activation invalide.', JsonResponse::HTTP_BAD_REQUEST),
        };
    }
}
