<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Service;

final class TradeInNumberGenerator
{
    public function generate(): string
    {
        return sprintf('REP-%s-%s', (new \DateTimeImmutable())->format('Ymd'), strtoupper(bin2hex(random_bytes(3))));
    }
}
