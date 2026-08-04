<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Cart\Application\DTO\AddCartItemInput;
use App\Module\Cart\Application\Projection\CartFormatter;
use App\Module\Cart\Application\Service\CartService;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items', name: 'api_public_cart_add_item', methods: ['POST'])]
#[RateLimited('public_api')]
class AddCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
        private readonly CartFormatter $cartFormatter,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = '' !== $request->getContent() ? \App\Infrastructure\Http\JsonPayload::decode($request) : [];

        $input = AddCartItemInput::fromArray($payload);
        $this->validator->validate($input);
        $productId = $input->productId;

        $product = $this->productRepository->find($productId);
        if (null === $product || !$product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $rentalMonths = null;
        if ('rental' === $product->getSellingType()) {
            if (null === $input->rentalMonths) {
                return ApiResponse::error('Champ "rentalMonths" requis pour ce produit.', JsonResponse::HTTP_BAD_REQUEST);
            }

            $rentalMonths = $input->rentalMonths;
        }

        $quantity = $input->quantity;

        $token = $this->extractToken($request);
        try {
            $cart = $this->cartService->addProduct($token, $product, $quantity, $rentalMonths);
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

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->headers->get('X-Cart-Token');

        if (is_string($headerToken) && '' !== $headerToken) {
            return $headerToken;
        }

        return null;
    }
}
