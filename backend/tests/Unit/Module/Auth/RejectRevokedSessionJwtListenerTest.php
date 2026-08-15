<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Infrastructure\Security\RejectRevokedSessionJwtListener;
use App\Module\Auth\Infrastructure\Security\SessionBoundJwtManager;
use App\Module\Auth\Infrastructure\Security\SymfonySecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class RejectRevokedSessionJwtListenerTest extends AuthIntegrationTestCase
{
    public function testRejectsRevokedSessionSelector(): void
    {
        $em = $this->entityManager();
        $user = $this->user('listener@example.com');
        $em->persist($user);
        $em->flush();

        $issued = $this->refreshService($em)->issueForUser($user);
        $selector = explode('.', $issued['refreshToken'], 2)[0];

        $listener = new RejectRevokedSessionJwtListener(new \App\Module\Auth\Application\Workflow\RefreshTokenRevocationService(
            $this->refreshRepository($em),
            new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em),
        ));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new SymfonySecurityUser($user));

        $listener(new JWTAuthenticatedEvent([
            SessionBoundJwtManager::SESSION_SELECTOR_CLAIM => $selector,
        ], $token));

        $this->refreshRepository($em)->findOneBySelector($selector)?->revoke();
        $em->flush();

        $this->expectException(InvalidTokenException::class);
        $listener(new JWTAuthenticatedEvent([
            SessionBoundJwtManager::SESSION_SELECTOR_CLAIM => $selector,
        ], $token));
    }
}
