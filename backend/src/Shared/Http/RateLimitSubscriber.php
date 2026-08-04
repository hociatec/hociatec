<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: 'kernel.controller')]
final readonly class RateLimitSubscriber
{
    public function __construct(
        private ContainerInterface $limiters,
        private Security $security,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        $attribute = $event->getAttributes(RateLimited::class)[0] ?? null;
        if (null === $attribute) {
            return;
        }

        $factory = $this->limiters->get($attribute->limiter);
        if (!$factory instanceof RateLimiterFactory) {
            throw new \LogicException(sprintf('Unknown rate limiter "%s".', $attribute->limiter));
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();
        $route = $request->attributes->get('_route');
        $scope = $attribute->limiter.':'.(is_string($route) && '' !== $route ? $route : $request->getPathInfo());
        $key = null !== $user ? $scope.':user:'.$user->getUserIdentifier() : $scope.':ip:'.($request->getClientIp() ?? 'unknown');
        $limit = $factory->create($key)->consume($attribute->tokens);
        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());
        $event->setController(static function () use ($limit, $retryAfter): JsonResponse {
            $response = ApiResponse::error(
                'Trop de requêtes, réessayez plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
            $response->headers->set('Retry-After', (string) $retryAfter);
            $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
            $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());

            return $response;
        });
    }
}
