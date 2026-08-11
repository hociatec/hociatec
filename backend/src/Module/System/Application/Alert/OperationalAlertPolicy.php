<?php

declare(strict_types=1);

namespace App\Module\System\Application\Alert;

final class OperationalAlertPolicy
{
    /**
     * @param array<string, float|int> $metrics
     *
     * @return list<OperationalAlert>
     */
    public function alertsFor(array $metrics): array
    {
        $alerts = [];

        $this->appendIfPositive($alerts, $metrics, 'hociatec_http_responses_total{status_class="5xx"}', 'warning', 'HTTP 5xx responses are increasing.');
        $this->appendIfPositive($alerts, $metrics, 'hociatec_webhook_failures_total', 'warning', 'Webhook deliveries are failing.');
        $this->appendIfPositive($alerts, $metrics, 'hociatec_payment_failed_total', 'warning', 'Payment failures are increasing.');
        $this->appendIfPositive($alerts, $metrics, 'hociatec_email_failures_total', 'warning', 'Email deliveries are failing.');
        $this->appendIfPositive($alerts, $metrics, 'hociatec_sql_slow_queries_total', 'warning', 'Slow SQL queries were detected.');
        $this->appendIfPositive($alerts, $metrics, 'hociatec_backup_failed_total', 'critical', 'Database backups are failing.');
        $this->appendIfPositive($alerts, $metrics, 'hociatec_outbox_dead_events', 'critical', 'Outbox events reached the dead-letter queue.');

        if (($metrics['hociatec_database_up'] ?? 1) <= 0) {
            $alerts[] = new OperationalAlert('critical', 'Database connectivity is down.', 'hociatec_database_up', 0);
        }

        return $alerts;
    }

    /**
     * @param list<OperationalAlert> $alerts
     * @param array<string, float|int> $metrics
     */
    private function appendIfPositive(array &$alerts, array $metrics, string $metric, string $severity, string $message): void
    {
        $value = $metrics[$metric] ?? 0;
        if ($value > 0) {
            $alerts[] = new OperationalAlert($severity, $message, $metric, $value);
        }
    }
}
