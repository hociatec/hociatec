<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\CsrfExempt;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Http\RateLimitKeyFactory;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Auth\Application\DTO\ResetPasswordInput;
use App\Module\Auth\Application\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/password-reset/reset/{token}', name: 'api_auth_password_reset_confirm', methods: ['POST'])]
#[CsrfExempt]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        private readonly DtoValidator $validator,
        private readonly RateLimitKeyFactory $rateLimitKeys,
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
            $payload = \App\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $input = ResetPasswordInput::fromArray($payload);
        $this->validator->validate($input);

        $limit = $this->limiter
            ->create($this->rateLimitKeys->forRequest($request, $token))
            ->consume(1);

        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de tentatives de réinitialisation. Veuillez réessayer plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        try {
            $this->passwordResetService->reset($token, $input->password);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }
}
