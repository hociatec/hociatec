<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Cart\Service\CartService;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\StripeCheckoutService;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Shared\Http\ApiResponse;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/checkout', name: 'api_orders_checkout', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimiter('checkout')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly StripeCheckoutService $stripeCheckout,
        private readonly OrderRepository $orderRepository,
        private readonly CartService $carts,
        private readonly ShippingAddressRepository $addresses,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if ($token === null || $token === '') {
            return ApiResponse::error('Token de panier manquant.', Response::HTTP_BAD_REQUEST);
        }

        $cart = $this->carts->findCartByToken($token);
        if ($cart === null) {
            return ApiResponse::error('Panier introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($cart->isConverted()) {
            $existingOrder = $this->resolveConvertedOrder($cart->getConvertedOrderId(), $user);
            if ($existingOrder !== null) {
                return ApiResponse::success(['order' => OrderFormatter::formatOrder($existingOrder)]);
            }

            return ApiResponse::error('Cette commande a déjà été validée.', Response::HTTP_CONFLICT);
        }

        if ($cart->getItems()->count() === 0) {
            return ApiResponse::error('Le panier est vide.', Response::HTTP_BAD_REQUEST);
        }

        $payload = [];
        try {
            $payload = (array) json_decode($request->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            // ignore and assume empty
        }

        $addressId = isset($payload['addressId']) ? (int) $payload['addressId'] : 0;
        $shipping = null;
        if ($addressId > 0) {
            $shipping = $this->addresses->findOneForUser($addressId, $user);
            if ($shipping === null) {
                return ApiResponse::error('Adresse de livraison invalide.', Response::HTTP_BAD_REQUEST);
            }
        } else {
            $shipping = $this->addresses->findFirstForUser($user);
            if ($shipping === null) {
                return ApiResponse::error('Aucune adresse de livraison trouvée.', Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $checkout = $this->stripeCheckout->createHostedCheckout($user, $cart, $shipping);
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();

            if ($message === 'Ce panier a deja ete valide.') {
                $existingOrder = $this->resolveConvertedOrder($cart->getConvertedOrderId(), $user);
                if ($existingOrder !== null) {
                    return ApiResponse::success(['order' => OrderFormatter::formatOrder($existingOrder)]);
                }

                return ApiResponse::error('Cette commande a déjà été validée.', Response::HTTP_CONFLICT, [$message]);
            }

            return ApiResponse::error('Impossible de valider la commande.', Response::HTTP_BAD_REQUEST, [$message]);
        } catch (\Throwable $e) {
            return ApiResponse::error('Impossible de valider la commande.', Response::HTTP_BAD_REQUEST, [$e->getMessage()]);
        }

        return ApiResponse::created([
            'mode' => 'redirect',
            'checkoutUrl' => $checkout->getCheckoutUrl(),
            'checkoutSessionId' => $checkout->getStripeSessionId(),
        ]);
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->headers->get('X-Cart-Token');
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }
        $queryToken = $request->query->get('cartToken');

        return is_string($queryToken) && $queryToken !== '' ? $queryToken : null;
    }

    private function resolveConvertedOrder(?int $orderId, User $user): ?\App\Module\Order\Entity\Order
    {
        if ($orderId === null) {
            return null;
        }

        $order = $this->orderRepository->find($orderId);
        if ($order === null || $order->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $order;
    }
}
