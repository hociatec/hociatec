<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/me', name: 'api_trade_ins_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListMyTradeInsController extends AbstractController
{
    public function __construct(
        private readonly TradeInRequestRepositoryPort $requests,
        private readonly TradeInFormatter $formatter,
    )
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();
        $items = $this->requests->findByUser($user, $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(array_map(fn ($item) => $this->formatter->format($item), $items), $pagination->metadata($this->requests->countByUser($user)));
    }
}
