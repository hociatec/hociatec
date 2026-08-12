<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Module\Quote\Application\Conversion\Exception\QuoteConversionResourceNotFoundException;
use App\Module\Quote\Application\Conversion\QuoteToOrderConverter;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/quotes/{reference}/convert-to-order', name: 'api_admin_operations_quote_convert', methods: ['POST'])]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class ConvertQuoteToOrderController
{
    public function __construct(private QuoteToOrderConverter $converter)
    {
    }

    public function __invoke(string $reference): JsonResponse
    {
        try {
            return ApiResponse::created($this->converter->convert($reference)->toArray());
        } catch (QuoteConversionResourceNotFoundException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Devis introuvable.', Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Conversion du devis impossible.', Response::HTTP_BAD_REQUEST);
        }
    }
}
