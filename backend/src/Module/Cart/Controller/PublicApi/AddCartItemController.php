<?php

declare(strict_types=1);

namespace App\Module\Cart\Controller\PublicApi;

use App\Module\Cart\Service\CartFormatter;
use App\Module\Cart\Service\CartService;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items', name: 'api_public_cart_add_item', methods: ['POST'])]
#[RateLimiter('public_api')]
class AddCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
        private readonly CartFormatter $cartFormatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '[]', true);

        if (!is_array($payload) || !isset($payload['productId'])) {
            return ApiResponse::error('Produit manquant.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $productId = (int) $payload['productId'];

        $product = $this->productRepository->find($productId);
        if ($product === null || !$product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $rentalMonths = null;
        if ($product->getSellingType() === 'rental') {
            if (!array_key_exists('rentalMonths', $payload)) {
                return ApiResponse::error('Champ "rentalMonths" requis pour ce produit.', JsonResponse::HTTP_BAD_REQUEST);
            }

            $rentalMonths = (int) $payload['rentalMonths'];
            if ($rentalMonths < 1) {
                return ApiResponse::error('La duree de location doit etre superieure ou egale a 1 mois.', JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $quantity = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;
        if ($quantity < 1) {
            $quantity = 1;
        }

        $token = $this->extractToken($request, $payload);
        try {
            $cart = $this->cartService->addProduct($token, $product, $quantity, $rentalMonths);
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
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

        if (isset($payload['cartToken']) && is_string($payload['cartToken']) && $payload['cartToken'] !== '') {
            return $payload['cartToken'];
        }

        return null;
    }
}
