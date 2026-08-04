<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\TradeIn\DTO\TradeInStatusInput;
use App\Module\TradeIn\Application\Service\TradeInService;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}/status', name: 'api_admin_trade_ins_status', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateTradeInStatusController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepository $requests, private readonly TradeInService $service, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $tradeIn = $this->requests->find($id);
        if (null === $tradeIn) {
            return ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND);
        }
        $input = TradeInStatusInput::fromArray(JsonPayload::decode($request));
        $this->validator->validate($input);
        $status = TradeInStatus::tryFrom($input->status);
        if (null === $status) {
            return ApiResponse::error('Statut invalide.');
        }
        try {
            $this->service->setStatus($tradeIn, $status);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(['status' => $status->value], 200, 'Le statut de la demande a été mis à jour.');
    }
}
