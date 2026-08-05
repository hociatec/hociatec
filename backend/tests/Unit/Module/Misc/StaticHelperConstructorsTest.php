<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Shared\Infrastructure\Http\ApiResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StaticHelperConstructorsTest extends TestCase
{
    #[DataProvider('helperClasses')]
    public function testPrivateConstructorsAreCovered(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance);

        self::assertInstanceOf($className, $instance);
    }

    /** @return list<array{string}> */
    public static function helperClasses(): array
    {
        return [
            [ApiResponse::class],
            [ProductFormValueNormalizer::class],
        ];
    }
}
