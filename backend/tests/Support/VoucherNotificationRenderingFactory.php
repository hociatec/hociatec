<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Module\Voucher\Application\Workflow\VoucherNotificationContextBuilder;
use App\Module\Voucher\Application\Workflow\VoucherNotificationRendering;
use App\Module\Voucher\Application\Workflow\VoucherNotificationTemplateRenderer;
use App\Module\Voucher\Application\Workflow\VoucherNotificationValidator;
use Symfony\Component\Clock\MockClock;

final class VoucherNotificationRenderingFactory
{
    private function __construct()
    {
    }

    public static function create(string $frontendUrl = 'https://front.example.test', string $now = '2026-07-26'): VoucherNotificationRendering
    {
        return new VoucherNotificationRendering(
            new VoucherNotificationValidator(new MockClock($now)),
            new VoucherNotificationContextBuilder($frontendUrl),
            new VoucherNotificationTemplateRenderer(),
        );
    }
}
