<?php

declare(strict_types=1);

namespace App\Module\Catalog\UI\Controller\PublicApi;

use App\Module\Catalog\Application\DTO\ShareProductInput;
use App\Module\Catalog\Application\Workflow\ProductQueryService;
use App\Module\Catalog\Application\Workflow\ProductShareEmailService;
use App\Shared\Application\Exception\MailDeliveryException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/catalog/products/{slug}/share', name: 'api_public_catalog_products_share', methods: ['POST'])]
#[RateLimited('product_share_public')]
final readonly class ShareProductEmailController
{
    public function __construct(
        private ProductQueryService $products,
        private ProductShareEmailService $sharing,
        private DtoValidator $dtoValidator,
        private RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.product_share_public')]
        private RateLimiterFactory $limiter,
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
        $limit = $this->limiter->create($this->rateLimitKeys->forRequest($request, $input->email))->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error('Trop de partages de produit. Veuillez réessayer plus tard.', JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

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
