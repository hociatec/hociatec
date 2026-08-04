<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\Service;

use App\Module\TradeIn\Application\Service\TradeInPersistence;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;

final readonly class DeleteTradeInRequestHandler
{
    public function __construct(
        private TradeInRequestRepository $requests,
        private TradeInPersistence $persistence,
    ) {
    }

    public function delete(TradeInRequest $request): void
    {
        $this->requests->delete($request);
        $this->persistence->commit();
    }
}
