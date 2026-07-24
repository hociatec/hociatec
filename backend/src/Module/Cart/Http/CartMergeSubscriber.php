<?php

declare(strict_types=1);

namespace App\Module\Cart\Http;

use App\Module\Cart\Service\CartMergeService;
use App\Module\User\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class CartMergeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CartMergeService $mergeService,
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
        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $headerToken = $request->headers->get('X-Cart-Token');
        $queryToken = $request->query->get('cartToken');
        $cartToken = is_string($headerToken) && '' !== $headerToken ? $headerToken : (is_string($queryToken) ? $queryToken : null);

        // Merge or attach if applicable
        $this->mergeService->mergeForUser($cartToken, $user);
    }
}
