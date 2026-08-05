<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Module\Admin\Application\TradeIn\Handler\DeleteTradeInRequestHandler;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}', name: 'api_admin_trade_ins_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteTradeInController extends AbstractController
{
    public function __construct(
        private readonly TradeInRequestRepositoryPort $requests,
        private readonly DeleteTradeInRequestHandler $deleteRequest,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $request = $this->requests->find($id);
        if (null === $request) {
            return ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->deleteRequest->delete($request);

        return ApiResponse::success([], Response::HTTP_OK, 'La demande de reprise a bien été supprimée.');
    }
}
