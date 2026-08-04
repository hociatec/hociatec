<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Order\Application\Service\OrderFormatter;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
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
        private readonly OrderCheckoutSessionRepository $checkoutSessions,
        private readonly OrderRepository $orders,
        private readonly OrderAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(string $stripeSessionId): JsonResponse
    {
        $checkout = $this->checkoutSessions->findOneByStripeSessionId($stripeSessionId);
        if (null === $checkout) {
            return ApiResponse::error('Session de paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->accessPolicy->canViewCheckoutSession($user, $checkout)) {
            return ApiResponse::error('Session de paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        $order = null !== $checkout->getOrderId() ? $this->orders->find($checkout->getOrderId()) : null;

        return ApiResponse::success([
            'status' => $checkout->getStatus(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
            'orderId' => $order?->getId(),
            'order' => null !== $order ? OrderFormatter::formatOrder($order) : null,
        ]);
    }
}
