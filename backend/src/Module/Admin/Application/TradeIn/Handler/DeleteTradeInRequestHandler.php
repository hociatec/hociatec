<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\Handler;

use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteTradeInRequestHandler
{
    public function __construct(
        private TradeInRequestRepositoryPort $requests,
        private UnitOfWork $persistence,
    ) {
    }

    public function delete(TradeInRequest $request): void
    {
        $this->requests->delete($request);
        $this->persistence->flush();
    }
}
