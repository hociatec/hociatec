<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\VerificationTokenHasher;
use App\Shared\Http\ApiResponse;
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
        private readonly UserRepository $users,
        #[Autowire(service: 'limiter.activation_verify')]
        private readonly RateLimiterFactory $activationVerifyLimiter,
    ) {
    }

    public function __invoke(string $token, Request $request): JsonResponse
    {
        if (!VerificationTokenHasher::isValidRawToken($token)) {
            return ApiResponse::error('Lien d\'activation invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        // Rate limit to avoid brute force token probing
        $limiter = $this->activationVerifyLimiter->create($request->getClientIp() ?? 'unknown');
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error('Trop de tentatives, réessayez plus tard.', JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $this->users->findOneByVerificationTokens(
            VerificationTokenHasher::hash($token),
            $token,
        );
        if (null === $user) {
            return ApiResponse::error('Lien d\'activation invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $now = new \DateTimeImmutable();
        $expiresAt = $user->getVerificationTokenExpiresAt();
        if ($user->isVerified()) {
            return ApiResponse::success(['message' => 'Votre compte est déjà activé.']);
        }
        if (null !== $expiresAt && $expiresAt < $now) {
            return ApiResponse::error('Le lien d\'activation a expiré.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt($now);
        $this->users->save($user, true);

        return ApiResponse::success(['message' => 'Votre compte a été activé avec succès.']);
    }
}
