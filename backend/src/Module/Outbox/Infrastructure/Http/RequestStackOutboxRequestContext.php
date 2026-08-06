<?php

declare(strict_types=1);

namespace App\Module\Outbox\Infrastructure\Http;

use App\Module\Outbox\Application\Port\OutboxRequestContextPort;
use App\Shared\Application\Http\RequestContext;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RequestStackOutboxRequestContext implements OutboxRequestContextPort
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function requestId(): ?string
    {
        $requestId = $this->requestStack->getCurrentRequest()?->attributes->get(RequestContext::REQUEST_ID_ATTRIBUTE);

        return \is_string($requestId) && '' !== $requestId ? $requestId : null;
    }
}
