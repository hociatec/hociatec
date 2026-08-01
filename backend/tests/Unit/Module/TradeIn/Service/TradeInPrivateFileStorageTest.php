<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\TradeIn\Service\TradeInPrivateFileStorage;
use PHPUnit\Framework\TestCase;

final class TradeInPrivateFileStorageTest extends TestCase
{
    public function testItReadsStoredReceiptFromPrivateDirectory(): void
    {
        $projectDir = $this->temporaryProjectDir();
        $storage = new TradeInPrivateFileStorage($projectDir);

        $relativePath = $storage->storeReceipt('%PDF-receipt');

        self::assertStringStartsWith('var/private/trade-ins/', $relativePath);
        self::assertSame('%PDF-receipt', $storage->read($relativePath));
    }

    public function testItRejectsPathTraversalOutsidePrivateDirectory(): void
    {
        $projectDir = $this->temporaryProjectDir();
        mkdir($projectDir.'/var/private/trade-ins', 0777, true);
        file_put_contents($projectDir.'/var/private/outside.pdf', '%PDF-outside');

        $storage = new TradeInPrivateFileStorage($projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Document privé introuvable.');

        $storage->read('var/private/trade-ins/../outside.pdf');
    }

    private function temporaryProjectDir(): string
    {
        $projectDir = sys_get_temp_dir().'/hociatec-trade-in-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0777, true);

        return $projectDir;
    }
}
