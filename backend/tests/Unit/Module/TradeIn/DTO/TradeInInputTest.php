<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\DTO;

use App\Module\TradeIn\DTO\TradeInInput;
use PHPUnit\Framework\TestCase;

final class TradeInInputTest extends TestCase
{
    public function testItParsesBooleanMultipartValues(): void
    {
        $input = TradeInInput::fromArray([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '0102030405',
            'category' => 'ordinateur',
            'productName' => 'Ordinateur',
            'purchasePriceCents' => '100000',
            'purchaseYear' => '2024',
            'conditionGrade' => 'bon',
            'functional' => 'true',
            'hasAccessories' => '1',
            'hasProofOfPurchase' => 'on',
            'description' => 'Description',
            'consent' => 'true',
        ]);

        self::assertTrue($input->functional);
        self::assertTrue($input->hasAccessories);
        self::assertTrue($input->hasProofOfPurchase);
        self::assertTrue($input->consent);
    }
}
