<?php

declare(strict_types=1);

namespace App\Module\Order\Controller;

use App\Module\Cart\Service\CartService;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Order\Service\OrderService;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
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
        private readonly OrderService $orders,
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

        if ($cart->getItems()->count() === 0) {
            return ApiResponse::error('Le panier est vide.', Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Resolve shipping address
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
            $order = $this->orders->createFromCartWithAddress($user, $cart, $shipping);
        } catch (\Throwable $e) {
            return ApiResponse::error('Impossible de valider la commande.', Response::HTTP_BAD_REQUEST, [$e->getMessage()]);
        }

        // Clear cart after successful order creation
        $this->carts->clearCart($token);

        return ApiResponse::created(OrderFormatter::formatOrder($order));
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
}
