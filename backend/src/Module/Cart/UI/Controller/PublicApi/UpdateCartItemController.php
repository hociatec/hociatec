<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Module\Cart\Application\DTO\UpdateCartItemInput;
use App\Module\Cart\Application\Projection\CartFormatter;
use App\Module\Cart\Application\Workflow\CartSessionWorkflow;
use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestHeaderValueResolver;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/cart/items/{productId}', name: 'api_public_cart_update_item', methods: ['PATCH'])]
#[RateLimited('public_api')]
final class UpdateCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartSessionWorkflow $cartService,
        private readonly ProductRepositoryPort $productRepository,
        private readonly CartFormatter $cartFormatter,
        private readonly DtoValidator $validator,
        private readonly RequestHeaderValueResolver $headers,
    ) {
    }

    public function __invoke(int $productId, Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::optionalPayload($request);

        $input = UpdateCartItemInput::fromArray($payload);
        $this->validator->validate($input);
        $quantity = $input->quantity;

        $token = $this->headers->nonEmptyString($request, 'X-Cart-Token');

        $product = $this->productRepository->find($productId);
        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $rentalMonths = null;
        $currentRentalMonths = null;
        $rentalMonths = $input->rentalMonths;
        $currentRentalMonths = $input->currentRentalMonths;

        if ('rental' === $product->getSellingType()) {
        } else {
            $rentalMonths = null;
            $currentRentalMonths = null;
        }

        try {
            $cart = $this->cartService->updateProductQuantity($token, $product, $quantity, $rentalMonths, $currentRentalMonths);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Impossible de mettre à jour cet article du panier.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
        ]);
        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
    }
}
