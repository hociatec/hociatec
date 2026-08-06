<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use PHPUnit\Framework\TestCase;

final class TradeInStatusTest extends TestCase
{
    public function testEachStatusProvidesALabel(): void
    {
        $expectedLabels = [
            TradeInStatus::SUBMITTED->value => 'Demande reçue',
            TradeInStatus::UNDER_REVIEW->value => 'En cours d’étude',
            TradeInStatus::OFFER_SENT->value => 'Offre envoyée',
            TradeInStatus::ACCEPTED->value => 'Offre acceptée',
            TradeInStatus::DECLINED->value => 'Offre refusée',
            TradeInStatus::RECEIVED->value => 'Matériel reçu',
            TradeInStatus::INSPECTED->value => 'Matériel inspecté',
            TradeInStatus::COMPLETED->value => 'Reprise terminée',
            TradeInStatus::CANCELLED->value => 'Demande annulée',
            TradeInStatus::EXPIRED->value => 'Offre expirée',
        ];

        foreach (TradeInStatus::cases() as $status) {
            self::assertSame($expectedLabels[$status->value], $status->label());
        }
    }
}
