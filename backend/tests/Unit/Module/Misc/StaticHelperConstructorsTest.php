<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Admin\Catalog\Service\ProductFormValueNormalizer;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Promotion\Service\PromotionFormatter;
use App\Module\Quote\Service\QuoteFormatter;
use App\Module\Rating\Service\ProductReviewFormatter;
use App\Module\User\Service\ShippingAddressFormatter;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Shared\Http\ApiResponse;
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
