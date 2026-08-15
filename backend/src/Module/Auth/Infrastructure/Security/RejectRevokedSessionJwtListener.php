<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Shared\Infrastructure\Http\SessionBoundJwtIssuer;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;

final readonly class RejectRevokedSessionJwtListener
{
    public function __construct(private RefreshTokenRevocationService $revocations)
    {
    }

    public function __invoke(JWTAuthenticatedEvent $event): void
    {
        $selector = $event->getPayload()[SessionBoundJwtIssuer::SESSION_SELECTOR_CLAIM] ?? null;
        if (!is_string($selector) || '' === $selector) {
            return;
        }

        $user = SymfonySecurityUser::domainUser($event->getToken()->getUser());
        if (null === $user) {
            throw new InvalidTokenException('JWT without domain user');
        }

        if (!$this->revocations->isSessionActiveForUser($user, $selector)) {
            throw new InvalidTokenException('JWT session revoked');
        }
    }
}
