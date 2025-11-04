<?php

declare(strict_types=1);

namespace App\Module\Cart\Controller\PublicApi;

use App\Module\Cart\Service\CartFormatter;
use App\Module\Cart\Service\CartService;
use App\Module\Catalog\Repository\ProductRepository;
use App\Shared\Http\ApiResponse;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items/{productId}', name: 'api_public_cart_update_item', methods: ['PATCH'])]
#[RateLimiter('public_api')]
final class UpdateCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function __invoke(int $productId, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '[]', true);

        if (!is_array($payload) || !array_key_exists('quantity', $payload)) {
            return ApiResponse::error('Champ "quantity" requis.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $quantity = (int) $payload['quantity'];
        if ($quantity < 0) {
            return ApiResponse::error('La quantite doit etre positive.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = $this->extractToken($request, $payload);

        $product = $this->productRepository->find($productId);
        if ($product === null) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $cart = $this->cartService->updateProductQuantity($token, $product, $quantity);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }

        $response = ApiResponse::success([
            'cart' => CartFormatter::formatCart($cart),
        ]);
        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractToken(Request $request, array $payload): ?string
    {
        $headerToken = $request->headers->get('X-Cart-Token');

        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $payloadToken = $payload['cartToken'] ?? null;

        return is_string($payloadToken) && $payloadToken !== '' ? $payloadToken : null;
    }
}
