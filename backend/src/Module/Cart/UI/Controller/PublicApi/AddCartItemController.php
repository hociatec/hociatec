<?php

declare(strict_types=1);

namespace App\Module\Cart\UI\Controller\PublicApi;

use App\Module\Cart\Application\DTO\AddCartItemInput;
use App\Module\Cart\Application\Projection\CartFormatter;
use App\Module\Cart\Application\Workflow\CartSessionWorkflow;
use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\Catalog\Domain\Entity\ProductSellingType;
use App\Module\Order\Application\Support\RentalPeriodCalculator;
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

#[Route('/api/public/cart/items', name: 'api_public_cart_add_item', methods: ['POST'])]
#[RateLimited('public_api')]
class AddCartItemController extends AbstractController
{
    public function __construct(
        private readonly CartSessionWorkflow $cartService,
        private readonly ProductRepositoryPort $productRepository,
        private readonly CartFormatter $cartFormatter,
        private readonly DtoValidator $validator,
        private readonly RequestHeaderValueResolver $headers,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::optionalPayload($request);

        $input = AddCartItemInput::fromArray($payload);
        $this->validator->validate($input);
        $productId = $input->productId;

        $product = $this->productRepository->find($productId);
        if (null === $product || !$product->isPublished()) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $sellingType = $input->sellingType ?? $product->resolveDisplaySellingType()->value;
            $sellingType = ProductSellingType::fromInput($sellingType)->value;
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Mode de commercialisation invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $rentalMonths = null;
        if ('rental' === $sellingType) {
            if (null === $input->rentalMonths) {
                return ApiResponse::error('Champ "rentalMonths" requis pour ce produit.', JsonResponse::HTTP_BAD_REQUEST);
            }
            if (null === $input->rentalStartDate || null === RentalPeriodCalculator::parseDate($input->rentalStartDate)) {
                return ApiResponse::error('Champ "rentalStartDate" requis pour ce produit.', JsonResponse::HTTP_BAD_REQUEST);
            }

            $rentalMonths = $input->rentalMonths;
        }

        $quantity = $input->quantity;

        $token = $this->headers->nonEmptyString($request, 'X-Cart-Token');
        $rentalStartDate = RentalPeriodCalculator::parseDate($input->rentalStartDate);
        try {
            $cart = $this->cartService->addProduct($token, $product, $sellingType, $quantity, $rentalMonths, $rentalStartDate);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Impossible d’ajouter cet article au panier.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        $response = ApiResponse::success([
            'cart' => $this->cartFormatter->formatCart($cart, $user instanceof User ? $user : null),
        ]);

        $response->headers->set('X-Cart-Token', $cart->getToken());

        return $response;
    }
}
