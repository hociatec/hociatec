<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\TradeIn\Service;

use App\Module\TradeIn\Application\Service\TradeInPersistence;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;

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
