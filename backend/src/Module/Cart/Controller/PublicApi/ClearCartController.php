<?php

declare(strict_types=1);

namespace App\Module\Cart\Controller\PublicApi;

use App\Module\Cart\Service\CartFormatter;
use App\Module\Cart\Service\CartService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart', name: 'api_public_cart_clear', methods: ['DELETE'])]
#[RateLimiter('public_api')]
final class ClearCartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CartFormatter $cartFormatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        $cart = $this->cartService->clearCart($token);
        $user = $this->getUser();

        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
        ]);
        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
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
