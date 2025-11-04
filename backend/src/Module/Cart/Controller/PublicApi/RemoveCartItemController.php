<?php

declare(strict_types=1);

namespace App\Module\Cart\Controller\PublicApi;

use App\Module\Cart\Service\CartFormatter;
use App\Module\Cart\Service\CartService;
use App\Module\Catalog\Repository\ProductRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items/{productId}', name: 'api_public_cart_remove_item', methods: ['DELETE'])]
#[RateLimiter('public_api')]
class RemoveCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function __invoke(int $productId, Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return ApiResponse::error('Token du panier manquant.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($productId);
        if ($product === null) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $cart = $this->cartService->removeProduct($token, $product);
        } catch (\InvalidArgumentException) {
            return ApiResponse::error('Panier introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $response = ApiResponse::success([
            'cart' => CartFormatter::formatCart($cart),
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
