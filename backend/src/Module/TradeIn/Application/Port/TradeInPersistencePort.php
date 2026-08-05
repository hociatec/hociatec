<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Port;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;

interface TradeInPersistencePort
{
    public function save(TradeInRequest $request): void;
    public function remove(TradeInRequest $request): void;
    public function commit(): void;
}
