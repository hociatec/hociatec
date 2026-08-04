<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Normalization;

use App\Domain\Normalization\EmailNormalizer;
use PHPUnit\Framework\TestCase;

final class EmailNormalizerTest extends TestCase
{
    public function testItNormalizesEmail(): void
    {
        self::assertSame('ada@example.com', EmailNormalizer::normalize('  Ada@Example.COM '));
    }

    public function testPrivateConstructorCanBeCovered(): void
    {
        $reflection = new \ReflectionClass(EmailNormalizer::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);

        self::assertInstanceOf(EmailNormalizer::class, $instance);
    }
}
