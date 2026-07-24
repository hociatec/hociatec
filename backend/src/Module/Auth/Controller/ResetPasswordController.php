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

#[Route('/api/auth/password-reset/reset/{token}', name: 'api_auth_password_reset_confirm', methods: ['POST'])]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        #[Autowire(service: 'limiter.password_reset_confirm')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(string $token, Request $request): JsonResponse
    {
        if (64 !== strlen($token) || !ctype_xdigit($token)) {
            return ApiResponse::error('Lien de réinitialisation invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $password = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['confirmPassword'] ?? '');

        if ($password !== $confirmPassword) {
            return ApiResponse::error(
                'Les mots de passe doivent être identiques.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return ApiResponse::error(
                'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $limit = $this->limiter
            ->create(($request->getClientIp() ?? 'unknown').':'.$token)
            ->consume(1);

        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de tentatives de réinitialisation. Veuillez réessayer plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        try {
            $this->passwordResetService->reset($token, $password);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }
}
