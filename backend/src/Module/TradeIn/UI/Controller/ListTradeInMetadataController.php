<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\TradeIn\Application\Projection\TradeInMetadataFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trade-ins/metadata', name: 'api_public_trade_ins_metadata', methods: ['GET'])]
final class ListTradeInMetadataController extends AbstractController
{
    public function __construct(private readonly TradeInMetadataFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['categories' => $this->formatter->categories(), 'conditions' => $this->formatter->conditions(), 'statuses' => $this->formatter->statuses(), 'paymentMethods' => $this->formatter->paymentMethods(), 'paymentStatuses' => $this->formatter->paymentStatuses()]);
    }
}
