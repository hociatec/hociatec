<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ValueObject;

use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function testUrlRejectsInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL invalide.');
        Url::fromString('not-a-url');
    }

    public function testMoneyRejectsNegativeValueThroughPrivateConstructor(): void
    {
        $reflection = new \ReflectionClass(Money::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $instance = $reflection->newInstanceWithoutConstructor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un montant ne peut pas être négatif.');
        $constructor->invoke($instance, -1);
    }
}
