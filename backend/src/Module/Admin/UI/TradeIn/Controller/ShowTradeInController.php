<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\TradeIn\Application\Service\TradeInFormatter;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}', name: 'api_admin_trade_ins_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ShowTradeInController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepository $requests)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $request = $this->requests->find($id);

        return null === $request ? ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND) : ApiResponse::success(['item' => TradeInFormatter::format($request)]);
    }
}
