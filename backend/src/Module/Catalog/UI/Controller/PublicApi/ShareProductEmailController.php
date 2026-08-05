<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Module\Catalog\Application\DTO\ShareProductInput;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Application\Workflow\ProductShareEmailService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Application\Exception\MailDeliveryException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products/{slug}/share', name: 'api_public_catalog_products_share', methods: ['POST'])]
#[RateLimited('product_share_public')]
final readonly class ShareProductEmailController
{
    public function __construct(
        private ProductQueryService $products,
        private ProductShareEmailService $sharing,
        private DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $product = $this->products->findPublishedBySlug($slug);
        if (null === $product) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $input = ShareProductInput::fromPayload(\App\Shared\Infrastructure\Http\JsonRequestInput::payload($request));
        $this->dtoValidator->validate($input);

        try {
            $this->sharing->send($product, $input->email);
        } catch (MailDeliveryException) {
            return ApiResponse::error(
                "Impossible d'envoyer le message pour le moment.",
                JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return ApiResponse::success([
            'sent' => true,
            'to' => $input->email,
            'message' => 'Le produit a été envoyé par e-mail.',
        ]);
    }
}
