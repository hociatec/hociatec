<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Module\User\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class SessionBoundJwtManager
{
    public const SESSION_SELECTOR_CLAIM = 'sid';

    public function __construct(private JWTTokenManagerInterface $jwtManager)
    {
    }

    public function createForSession(User $user, string $sessionSelector): string
    {
        return $this->jwtManager->createFromPayload(
            new SymfonySecurityUser($user),
            [self::SESSION_SELECTOR_CLAIM => $sessionSelector],
        );
    }
}
