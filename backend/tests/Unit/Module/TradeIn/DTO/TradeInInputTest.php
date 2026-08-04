<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\DTO;

use App\Module\TradeIn\Application\DTO\TradeInInput;
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

    public function testItCanCloneContactDetailsWithWithContact(): void
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
            'functional' => true,
            'hasAccessories' => false,
            'hasProofOfPurchase' => false,
            'description' => 'Description',
            'consent' => true,
        ]);

        $updated = $input->withContact('Grace', 'Hopper', 'grace@example.com', '0607080910');

        self::assertSame('Grace', $updated->firstName);
        self::assertSame('Hopper', $updated->lastName);
        self::assertSame('grace@example.com', $updated->email);
        self::assertSame('0607080910', $updated->phone);
        self::assertSame($input->category, $updated->category);
        self::assertSame($input->productName, $updated->productName);
    }
}
