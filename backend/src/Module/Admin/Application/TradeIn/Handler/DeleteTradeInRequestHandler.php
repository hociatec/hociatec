<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\Handler;

use App\Module\TradeIn\Application\Port\TradeInPersistencePort;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;

final readonly class DeleteTradeInRequestHandler
{
    public function __construct(
        private TradeInRequestRepositoryPort $requests,
        private TradeInPersistencePort $persistence,
    ) {
    }

    public function delete(TradeInRequest $request): void
    {
        $this->requests->delete($request);
        $this->persistence->flush();
    }
}
