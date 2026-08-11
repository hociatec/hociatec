<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\System;

use App\Module\System\Application\Alert\OperationalAlertPolicy;
use PHPUnit\Framework\TestCase;

final class OperationalAlertPolicyTest extends TestCase
{
    public function testPolicyReturnsNoAlertWhenEverythingIsHealthy(): void
    {
        $policy = new OperationalAlertPolicy();

        self::assertSame([], $policy->alertsFor([
            'hociatec_database_up' => 1,
            'hociatec_http_responses_total{status_class="5xx"}' => 0,
            'hociatec_webhook_failures_total' => 0,
            'hociatec_payment_failed_total' => 0,
            'hociatec_email_failures_total' => 0,
            'hociatec_sql_slow_queries_total' => 0,
            'hociatec_backup_failed_total' => 0,
            'hociatec_outbox_dead_events' => 0,
        ]));
    }

    public function testPolicyCoversCriticalAndWarningOperationalFailures(): void
    {
        $alerts = (new OperationalAlertPolicy())->alertsFor([
            'hociatec_database_up' => 0,
            'hociatec_http_responses_total{status_class="5xx"}' => 4,
            'hociatec_webhook_failures_total' => 2,
            'hociatec_payment_failed_total' => 3,
            'hociatec_email_failures_total' => 1,
            'hociatec_sql_slow_queries_total' => 5,
            'hociatec_backup_failed_total' => 1,
            'hociatec_outbox_dead_events' => 2,
        ]);

        self::assertCount(8, $alerts);
        self::assertSame('warning', $alerts[0]->severity);
        self::assertSame('critical', $alerts[5]->severity);
        self::assertSame('hociatec_backup_failed_total', $alerts[5]->metric);
        self::assertSame('critical', $alerts[7]->severity);
        self::assertSame('hociatec_database_up', $alerts[7]->metric);
    }
}
