<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Port;

interface TradeInReceiptRenderer
{
    public function render(string $html): string;
}
