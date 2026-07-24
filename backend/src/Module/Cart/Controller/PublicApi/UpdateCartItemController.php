<?php

declare(strict_types=1);

namespace App\Module\Cart\Controller\PublicApi;

use App\Module\Cart\Service\CartFormatter;
use App\Module\Cart\Service\CartService;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items/{productId}', name: 'api_public_cart_update_item', methods: ['PATCH'])]
#[RateLimited('public_api')]
final class UpdateCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
        private readonly CartFormatter $cartFormatter,
    ) {
    }

    public function __invoke(int $productId, Request $request): JsonResponse
    {
        $payload = '' !== $request->getContent() ? $request->toArray() : [];

        if (!array_key_exists('quantity', $payload)) {
            return ApiResponse::error('Champ "quantity" requis.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $quantity = (int) $payload['quantity'];
        if ($quantity < 0) {
            return ApiResponse::error('La quantite doit etre positive.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = $this->extractToken($request, $payload);

        $product = $this->productRepository->find($productId);
        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $rentalMonths = null;
        $currentRentalMonths = null;
        if (array_key_exists('rentalMonths', $payload)) {
            $rentalMonths = (int) $payload['rentalMonths'];
        }

        if (array_key_exists('currentRentalMonths', $payload)) {
            $currentRentalMonths = (int) $payload['currentRentalMonths'];
        }

        if ('rental' === $product->getSellingType()) {
            if (null !== $rentalMonths && $rentalMonths < 1) {
                return ApiResponse::error('La duree de location doit etre superieure ou egale a 1 mois.', JsonResponse::HTTP_BAD_REQUEST);
            }

            if (null !== $currentRentalMonths && $currentRentalMonths < 1) {
                return ApiResponse::error('La duree de location doit etre superieure ou egale a 1 mois.', JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            $rentalMonths = null;
            $currentRentalMonths = null;
        }

        try {
            $cart = $this->cartService->updateProductQuantity($token, $product, $quantity, $rentalMonths, $currentRentalMonths);
        } catch (\InvalidArgumentException $exception) {
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

        if (is_string($headerToken) && '' !== $headerToken) {
            return $headerToken;
        }

        $payloadToken = $payload['cartToken'] ?? null;

        return is_string($payloadToken) && '' !== $payloadToken ? $payloadToken : null;
    }
}
