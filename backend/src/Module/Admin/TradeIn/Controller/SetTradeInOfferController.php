<?php

declare(strict_types=1);

namespace App\Module\Admin\TradeIn\Controller;

use App\Module\Admin\TradeIn\DTO\TradeInOfferInput;
use App\Module\TradeIn\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Service\TradeInService;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trade-ins/{id}/offer', name: 'api_admin_trade_ins_offer', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
final class SetTradeInOfferController extends AbstractController
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
        $input = TradeInOfferInput::fromArray(JsonPayload::decode($request));
        $this->validator->validate($input);
        $expires = null;
        if (null !== $input->offerExpiresAt && '' !== $input->offerExpiresAt) {
            try {
                $expires = new \DateTimeImmutable($input->offerExpiresAt);
            } catch (\Throwable) {
                return ApiResponse::error('Date d’expiration invalide.');
            }
        }
        try {
            $this->service->setOffer($tradeIn, (int) $input->offerCents, $expires, $input->adminNote);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(['status' => 'offer_sent', 'offerCents' => $tradeIn->getOfferCents()], 200, 'L’offre de reprise a été enregistrée.');
    }
}
