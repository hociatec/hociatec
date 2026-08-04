<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins/me', name: 'api_trade_ins_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListMyTradeInsController extends AbstractController
{
    public function __construct(private readonly TradeInRequestRepository $requests)
    {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::successItem('items', array_map(static fn ($item) => TradeInFormatter::format($item), $this->requests->findByUser($user)));
    }
}
