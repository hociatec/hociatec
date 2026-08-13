<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Module\Cart\Application\Exception\CartNotFoundException;
use App\Module\Cart\Application\Projection\CartFormatter;
use App\Module\Cart\Application\Workflow\CartSessionWorkflow;
use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestHeaderValueResolver;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items/{productId}', name: 'api_public_cart_remove_item', methods: ['DELETE'])]
#[RateLimited('public_api')]
class RemoveCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartSessionWorkflow $cartService,
        private readonly ProductRepositoryPort $productRepository,
        private readonly CartFormatter $cartFormatter,
        private readonly RequestHeaderValueResolver $headers,
    ) {
    }

    public function __invoke(int $productId, Request $request): JsonResponse
    {
        $token = $this->headers->nonEmptyString($request, 'X-Cart-Token');

        if (null === $token) {
            return ApiResponse::error('Token du panier manquant.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($productId);
        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $rentalMonths = RequestQueryMapper::positiveIntFromAny($request, ['currentRentalMonths', 'rentalMonths']);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Paramètres de suppression invalides.', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $cart = $this->cartService->removeProduct($token, $product, $rentalMonths);
        } catch (CartNotFoundException|\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception);
        }

        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
        ]);

        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
    }
}
