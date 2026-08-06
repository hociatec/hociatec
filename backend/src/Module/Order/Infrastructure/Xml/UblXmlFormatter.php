<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

final class UblXmlFormatter
{
    public function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public function percent(int $rateBps): string
    {
        return number_format($rateBps / 100, 2, '.', '');
    }
}
