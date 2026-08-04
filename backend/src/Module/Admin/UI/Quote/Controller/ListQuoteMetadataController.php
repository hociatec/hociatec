<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Mapper\QuoteStatusTranslator;
use App\Module\Quote\Domain\Enum\ServiceBillingMode;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/metadata', name: 'api_admin_quotes_metadata', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListQuoteMetadataController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'statuses' => QuoteStatusTranslator::options(),
            'serviceBillingModes' => ServiceBillingMode::options(),
        ]);
    }
}
