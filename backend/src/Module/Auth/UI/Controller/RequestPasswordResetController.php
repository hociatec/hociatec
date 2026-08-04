<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\Application\DTO\RequestPasswordResetInput;
use App\Module\Auth\Application\Workflow\PasswordResetService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\CsrfExempt;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/password-reset/request', name: 'api_auth_password_reset_request', methods: ['POST'])]
#[CsrfExempt]
class RequestPasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        private readonly DtoValidator $validator,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.password_reset_request')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $input = RequestPasswordResetInput::fromArray($payload);
        $this->validator->validate($input);

        $limit = $this->limiter
            ->create($this->rateLimitKeys->forRequest($request, $input->email))
            ->consume(1);

        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de demandes de réinitialisation. Veuillez réessayer plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $this->passwordResetService->request($input->email);

        return ApiResponse::success([
            'message' => 'Si un compte correspond à cette adresse e-mail, un lien de réinitialisation vient d’être envoyé.',
        ]);
    }
}
