<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/checkout/sessions/{stripeSessionId}', name: 'api_orders_checkout_session_status', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class CheckoutSessionStatusController extends AbstractController
{
    public function __construct(
        private readonly OrderCheckoutSessionRepositoryPort $checkoutSessions,
        private readonly OrderRepositoryPort $orders,
        private readonly OrderAccessPolicy $accessPolicy,
        private readonly OrderFormatter $orderFormatter,
    ) {
    }

    public function __invoke(string $stripeSessionId): JsonResponse
    {
        $checkout = $this->checkoutSessions->findOneByStripeSessionId($stripeSessionId);
        if (null === $checkout) {
            return ApiResponse::error('Session de paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return ApiResponse::error('Session de paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        $order = null !== $checkout->getOrderId() ? $this->orders->find($checkout->getOrderId()) : null;

        return ApiResponse::success([
            'status' => $checkout->getStatus(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
            'orderId' => $order?->getId(),
            'order' => null !== $order ? $this->orderFormatter->formatOrder($order) : null,
        ]);
    }
}
