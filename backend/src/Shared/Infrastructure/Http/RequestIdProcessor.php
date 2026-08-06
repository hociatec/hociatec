<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Module\Auth\Infrastructure\Security\SymfonySecurityUser;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class RequestIdProcessor
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    /**
     * Monolog v3 signature.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $record;
        }

        $ctx = $record->context;
        $ctx['request_id'] = (string) ($request->attributes->get(RequestIdSubscriber::ATTRIBUTE) ?? '');
        $ctx['method'] = $request->getMethod();
        $ctx['path'] = $request->getPathInfo();
        $ctx['ip'] = $request->getClientIp();
        $ctx['route'] = (string) ($request->attributes->get('_route') ?? '');

        if (null !== $this->tokenStorage && ($token = $this->tokenStorage->getToken())) {
            $user = SymfonySecurityUser::domainUser($token->getUser());
            if (null !== $user) {
                $ctx['user_id'] = $user->getId();
            }
        }

        return $record->with(context: $ctx);
    }
}
