<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

final readonly class InvoiceDocument
{
    public function __construct(
        public string $pdf,
        public string $xml,
    ) {
    }
}
