<?php

declare(strict_types=1);

namespace App\Module\Cart\Infrastructure\Http;

use App\Module\Cart\Application\Workflow\CartMergeService;
use App\Module\User\Domain\Entity\User;
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
        $cartToken = $request->headers->get('X-Cart-Token');
        if (!is_string($cartToken) || '' === $cartToken) {
            return;
        }

        // Merge or attach if applicable
        $this->mergeService->mergeForUser($cartToken, $user);
    }
}
