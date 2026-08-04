<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Admin\Application\Catalog\Service\ProductFormValueNormalizer;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
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
            [CatalogFormatter::class],
            [ProductFormValueNormalizer::class],
            [OrderFormatter::class],
            [PromotionFormatter::class],
            [QuoteFormatter::class],
            [ProductReviewFormatter::class],
            [ShippingAddressFormatter::class],
            [VoucherFormatter::class],
        ];
    }
}
