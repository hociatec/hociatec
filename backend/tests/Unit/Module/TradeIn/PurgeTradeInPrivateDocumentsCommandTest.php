<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn;

use App\Module\TradeIn\Infrastructure\Command\PurgeTradeInPrivateDocumentsCommand;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeTradeInPrivateDocumentsCommandTest extends TradeInIntegrationTestCase
{
    public function testItPurgesClosedPrivateDocumentsPastRetention(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $em->persist($user);
        $request = $this->tradeInRequest($user, 'TR-PURGE');
        $request->setClosure(10_000, 'cash', 'paid', null, new \DateTimeImmutable('2026-01-01T10:00:00+00:00'));
        $request->setRib('var/private/trade-ins/rib-purge.pdf', 'rib.pdf', 4, hash('sha256', 'rib'));
        $request->setReceiptPath('var/private/trade-ins/receipt-purge.pdf');

        $reflection = new \ReflectionObject($request);
        $closedAt = $reflection->getProperty('closedAt');
        $closedAt->setValue($request, new \DateTimeImmutable('2026-01-01T10:00:00+00:00'));

        $em->persist($request);
        $em->flush();

        file_put_contents($this->projectDir().'/var/private/trade-ins/rib-purge.pdf', '%PDF-rib');
        file_put_contents($this->projectDir().'/var/private/trade-ins/receipt-purge.pdf', '%PDF-receipt');

        $tester = new CommandTester(new PurgeTradeInPrivateDocumentsCommand(
            new TradeInRequestRepository($this->registry($em)),
            new TradeInPrivateFileStorage($this->projectDir()),
            new TradeInPersistence($em),
        ));
        self::assertSame(0, $tester->execute(['--retention-days' => '30', '--limit' => '10']));

        self::assertStringContainsString('1 demande(s) de reprise purgée(s).', $tester->getDisplay());
        self::assertFileDoesNotExist($this->projectDir().'/var/private/trade-ins/rib-purge.pdf');
        self::assertFileDoesNotExist($this->projectDir().'/var/private/trade-ins/receipt-purge.pdf');
        self::assertNull($request->getRibPath());
        self::assertNull($request->getReceiptPath());
    }
}
