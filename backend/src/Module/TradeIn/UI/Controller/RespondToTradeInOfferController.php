<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Workflow\CustomerTradeInPortalService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/{id}/respond/{action}', name: 'api_trade_ins_respond', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class RespondToTradeInOfferController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerTradeInPortalService $portal,
    ) {
    }

    public function __invoke(int $id, string $action): JsonResponse
    {
        try {
            $result = $this->portal->respondToOfferForUser($this->currentUser(), $id, $action);
        } catch (\DomainException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Réponse à l’offre impossible.', Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Action de reprise invalide.', Response::HTTP_BAD_REQUEST);
        }
        if (null === $result) {
            return ApiResponse::error('Demande de reprise introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($result, 200, 'Votre réponse à l’offre a bien été enregistrée.');
    }
}
