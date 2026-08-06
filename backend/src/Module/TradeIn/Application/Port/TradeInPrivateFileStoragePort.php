<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Port;

interface TradeInPrivateFileStoragePort
{
    /** @return array{path: string, originalName: string, size: int, sha256: string} */
    public function storeRib(object $file): array;

    public function storeReceipt(string $pdf): string;

    public function read(string $relativePath): string;
}
