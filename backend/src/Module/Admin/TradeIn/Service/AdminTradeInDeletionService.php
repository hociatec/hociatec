<?php

declare(strict_types=1);

namespace App\Module\Admin\TradeIn\Service;

use App\Module\TradeIn\Entity\TradeInRequest;
use App\Module\TradeIn\Service\TradeInPersistence;

final readonly class AdminTradeInDeletionService
{
    public function __construct(private TradeInPersistence $persistence)
    {
    }

    public function delete(TradeInRequest $request): void
    {
        $this->persistence->remove($request);
    }
}
