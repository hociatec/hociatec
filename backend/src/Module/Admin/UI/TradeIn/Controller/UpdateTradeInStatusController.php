<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Module\Admin\Application\TradeIn\DTO\TradeInStatusInput;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Application\Workflow\TradeInRequestWorkflow;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}/status', name: 'api_admin_trade_ins_status', methods: ['PUT'])]
#[IsGranted('ROLE_TRADE_INS_MANAGER')]
final class UpdateTradeInStatusController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepositoryPort $requests, private readonly TradeInRequestWorkflow $service, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $tradeIn = $this->requests->find($id);
        if (null === $tradeIn) {
            return ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND);
        }
        $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, TradeInStatusInput::class);
        $this->validator->validate($input);
        $status = TradeInStatus::tryFrom($input->status);
        if (null === $status) {
            return ApiResponse::error('Statut invalide.');
        }
        try {
            $this->service->setStatus($tradeIn, $status);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Mise à jour du statut impossible.', Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(['status' => $status->value], 200, 'Le statut de la demande a été mis à jour.');
    }
}
