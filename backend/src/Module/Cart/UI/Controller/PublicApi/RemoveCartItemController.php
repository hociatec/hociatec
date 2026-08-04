<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Module\Cart\Application\Service\CartFormatter;
use App\Module\Cart\Application\Service\CartService;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items/{productId}', name: 'api_public_cart_remove_item', methods: ['DELETE'])]
#[RateLimited('public_api')]
class RemoveCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
        private readonly CartFormatter $cartFormatter,
    ) {
    }

    public function __invoke(int $productId, Request $request): JsonResponse
    {
        $token = $this->extractToken($request);

        if (null === $token) {
            return ApiResponse::error('Token du panier manquant.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($productId);
        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $queryRentalMonths = $this->extractRentalMonths($request);
        if ($queryRentalMonths instanceof JsonResponse) {
            return $queryRentalMonths;
        }
        $rentalMonths = $queryRentalMonths;

        try {
            $cart = $this->cartService->removeProduct($token, $product, $rentalMonths);
        } catch (\InvalidArgumentException $exception) {
            $status = 'Panier introuvable.' === $exception->getMessage()
                ? JsonResponse::HTTP_NOT_FOUND
                : JsonResponse::HTTP_BAD_REQUEST;

            return ApiResponse::error($exception->getMessage(), $status);
        }

        $user = $this->getUser();
        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
        ]);

        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
    }

    private function extractRentalMonths(Request $request): JsonResponse|int|null
    {
        $monthsParam = $request->query->get('currentRentalMonths', $request->query->get('rentalMonths'));

        if (null === $monthsParam || '' === $monthsParam) {
            return null;
        }

        if (!is_numeric($monthsParam)) {
            return ApiResponse::error('Le nombre de mois doit etre un entier positif.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $months = (int) $monthsParam;

        if ($months < 1) {
            return ApiResponse::error('La duree de location doit etre superieure ou egale a 1 mois.', JsonResponse::HTTP_BAD_REQUEST);
        }

        return $months;
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->headers->get('X-Cart-Token');

        if (is_string($headerToken) && '' !== $headerToken) {
            return $headerToken;
        }

        return null;
    }
}
