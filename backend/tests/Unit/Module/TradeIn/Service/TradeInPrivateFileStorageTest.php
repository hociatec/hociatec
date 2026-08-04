<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\TradeIn\Application\Storage\TradeInPrivateFileStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function testItStoresValidRibPdfAndRejectsInvalidRibFiles(): void
    {
        $projectDir = $this->temporaryProjectDir();
        $storage = new TradeInPrivateFileStorage($projectDir);
        $pdfPath = $projectDir.'/rib.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 rib');

        $stored = $storage->storeRib(new UploadedFile($pdfPath, 'rib.pdf', 'application/pdf', null, true));

        self::assertStringStartsWith('var/private/trade-ins/', $stored['path']);
        self::assertSame('rib.pdf', $stored['originalName']);
        self::assertSame(strlen('%PDF-1.4 rib'), $stored['size']);
        self::assertSame(hash('sha256', '%PDF-1.4 rib'), $stored['sha256']);
        self::assertSame('%PDF-1.4 rib', $storage->read($stored['path']));

        $textPath = $projectDir.'/rib.txt';
        file_put_contents($textPath, 'plain text');
        try {
            $storage->storeRib(new UploadedFile($textPath, 'rib.txt', 'text/plain', null, true));
            self::fail('Expected invalid MIME exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le RIB doit être fourni au format PDF.', $exception->getMessage());
        }

        $badPdfPath = $projectDir.'/bad.pdf';
        file_put_contents($badPdfPath, 'not pdf');
        try {
            $storage->storeRib(new class($badPdfPath) extends UploadedFile {
                public function __construct(string $path)
                {
                    parent::__construct($path, 'bad.pdf', 'application/pdf', null, true);
                }

                public function getMimeType(): ?string
                {
                    return 'application/pdf';
                }
            });
            self::fail('Expected invalid PDF contents exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le fichier fourni n’est pas un PDF valide.', $exception->getMessage());
        }
    }

    public function testReadRejectsMissingPrivateDocument(): void
    {
        $storage = new TradeInPrivateFileStorage($this->temporaryProjectDir());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Document privé introuvable.');

        $storage->read('var/private/trade-ins/missing.pdf');
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
