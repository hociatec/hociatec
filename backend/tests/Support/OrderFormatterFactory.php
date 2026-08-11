<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Projection\OrderItemFormatter;
use App\Module\Order\Application\Projection\OrderStatusLabelFormatter;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;

final class OrderFormatterFactory
{
    private function __construct()
    {
    }

    public static function create(): OrderFormatter
    {
        return new OrderFormatter(
            new OrderStatusLabelFormatter(),
            new OrderItemFormatter(new ProductReviewFormatter()),
            new OrderStatusWorkflow(),
        );
    }
}
