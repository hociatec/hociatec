<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\TradeIn\Controller;

use App\Module\Admin\Application\TradeIn\DTO\TradeInClosureInput;
use App\Module\TradeIn\Application\Workflow\TradeInClosureService;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}/close', name: 'api_admin_trade_ins_close', methods: ['POST'])]
#[IsGranted('ROLE_TRADE_INS_MANAGER')]
final class CloseTradeInController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepositoryPort $requests, private readonly TradeInClosureService $closure, private readonly DtoValidator $validator)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $tradeIn = $this->requests->find($id);
        if (null === $tradeIn) {
            return ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND);
        }

        $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, TradeInClosureInput::class);
        $this->validator->validate($input, message: 'Les informations de clôture sont invalides.');
        try {
            $this->closure->close($tradeIn, $input);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(['closed' => true], 200, 'La reprise a été clôturée et le justificatif a été généré.');
    }
}
