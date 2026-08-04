<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\Service;

use App\Module\TradeIn\Application\Persistence\TradeInPersistence;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;

final readonly class DeleteTradeInRequestHandler
{
    public function __construct(
        private TradeInRequestRepositoryPort $requests,
        private TradeInPersistence $persistence,
    ) {
    }

    public function delete(TradeInRequest $request): void
    {
        $this->requests->delete($request);
        $this->persistence->commit();
    }
}
