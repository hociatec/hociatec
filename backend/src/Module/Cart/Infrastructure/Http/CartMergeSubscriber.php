<?php

declare(strict_types=1);

namespace App\Module\Cart\Infrastructure\Http;

use App\Module\Auth\Infrastructure\Security\SymfonySecurityUser;
use App\Module\Cart\Application\Workflow\CartMergeService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\RequestHeaderValueResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class CartMergeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CartMergeService $mergeService,
        private readonly RequestHeaderValueResolver $headers,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 50]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }
        $user = SymfonySecurityUser::domainUser($token->getUser());
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $cartToken = $this->headers->nonEmptyString($request, 'X-Cart-Token');
        if (null === $cartToken) {
            return;
        }

        // Merge or attach if applicable
        $this->mergeService->mergeForUser($cartToken, $user);
    }
}
