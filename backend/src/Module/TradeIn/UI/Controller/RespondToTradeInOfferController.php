<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Workflow\TradeInService;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/{id}/respond/{action}', name: 'api_trade_ins_respond', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class RespondToTradeInOfferController extends AbstractController
{
    public function __construct(
        private readonly TradeInRequestRepositoryPort $requests,
        private readonly TradeInService $service,
        private readonly TradeInAccessPolicy $accessPolicy,
    )
    {
    }

    public function __invoke(int $id, string $action): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $request = $this->requests->find($id);
        if (null === $request || !$this->accessPolicy->canRespondToOffer($user, $request)) {
            return ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND);
        }
        if (TradeInStatus::OFFER_SENT !== $request->getStatus() || null === $request->getOfferCents()) {
            return ApiResponse::error('Aucune offre n’est disponible.', Response::HTTP_CONFLICT);
        }
        $status = 'accept' === $action ? TradeInStatus::ACCEPTED : ('decline' === $action ? TradeInStatus::DECLINED : null);
        if (null === $status) {
            return ApiResponse::error('Réponse invalide.', Response::HTTP_BAD_REQUEST);
        }
        $this->service->setStatus($request, $status);

        return ApiResponse::success(['status' => $status->value], 200, 'Votre réponse à l’offre a bien été enregistrée.');
    }
}
