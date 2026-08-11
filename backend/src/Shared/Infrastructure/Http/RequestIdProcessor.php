<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Security\AuthenticatedUserProvider;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestIdProcessor
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AuthenticatedUserProvider $authenticatedUserProvider,
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

        $user = $this->authenticatedUserProvider->currentUser();
        if (null !== $user) {
            $ctx['user_id'] = $user->getId();
        }

        return $record->with(context: $ctx);
    }
}
