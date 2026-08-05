<?php

declare(strict_types=1);

namespace App\Module\System\Application\Provider;

use App\Module\Outbox\Application\OutboxMetrics;

final readonly class PrometheusMetricContractProvider
{
    /** @return list<string> */
    public function baseLines(int $databaseUp): array
    {
        return [
            '# HELP hociatec_info Application information.',
            '# TYPE hociatec_info gauge',
            'hociatec_info{php_version="'.PHP_VERSION.'"} 1',
            '# HELP hociatec_metrics_endpoint_up Metrics endpoint availability.',
            '# TYPE hociatec_metrics_endpoint_up gauge',
            'hociatec_metrics_endpoint_up 1',
            '# HELP hociatec_observability_pipeline_info Observability pipeline contract.',
            '# TYPE hociatec_observability_pipeline_info gauge',
            'hociatec_observability_pipeline_info{format="prometheus",logs="json",request_id="enabled"} 1',
            '# HELP hociatec_database_up Database availability.',
            '# TYPE hociatec_database_up gauge',
            'hociatec_database_up '.$databaseUp,
            '# HELP hociatec_http_request_duration_seconds HTTP endpoint latency histogram.',
            '# TYPE hociatec_http_request_duration_seconds histogram',
            'hociatec_http_request_duration_seconds_bucket{le="0.1"} 0',
            'hociatec_http_request_duration_seconds_bucket{le="0.5"} 0',
            'hociatec_http_request_duration_seconds_bucket{le="1"} 0',
            'hociatec_http_request_duration_seconds_bucket{le="+Inf"} 0',
            'hociatec_http_request_duration_seconds_sum 0',
            'hociatec_http_request_duration_seconds_count 0',
            '# HELP hociatec_http_responses_total HTTP responses grouped by status class.',
            '# TYPE hociatec_http_responses_total counter',
            'hociatec_http_responses_total{status_class="4xx"} 0',
            'hociatec_http_responses_total{status_class="5xx"} 0',
            '# HELP hociatec_payment_failed_total Failed payment events.',
            '# TYPE hociatec_payment_failed_total counter',
            'hociatec_payment_failed_total 0',
            '# HELP hociatec_pdf_generation_duration_seconds PDF generation duration histogram.',
            '# TYPE hociatec_pdf_generation_duration_seconds histogram',
            'hociatec_pdf_generation_duration_seconds_bucket{le="1"} 0',
            'hociatec_pdf_generation_duration_seconds_bucket{le="5"} 0',
            'hociatec_pdf_generation_duration_seconds_bucket{le="+Inf"} 0',
            'hociatec_pdf_generation_duration_seconds_sum 0',
            'hociatec_pdf_generation_duration_seconds_count 0',
            '# HELP hociatec_email_failures_total Email delivery failures.',
            '# TYPE hociatec_email_failures_total counter',
            'hociatec_email_failures_total 0',
            '# HELP hociatec_sql_slow_queries_total Slow SQL queries.',
            '# TYPE hociatec_sql_slow_queries_total counter',
            'hociatec_sql_slow_queries_total 0',
            '# HELP hociatec_admin_sensitive_actions_total Sensitive administrative actions.',
            '# TYPE hociatec_admin_sensitive_actions_total counter',
            'hociatec_admin_sensitive_actions_total 0',
        ];
    }

    /** @return list<string> */
    public function outboxLines(OutboxMetrics $metrics): array
    {
        return [
            '# HELP hociatec_outbox_pending_events Pending or retryable outbox events.',
            '# TYPE hociatec_outbox_pending_events gauge',
            'hociatec_outbox_pending_events '.$metrics->pendingEvents,
            '# HELP hociatec_outbox_oldest_pending_age_seconds Age of the oldest pending outbox event.',
            '# TYPE hociatec_outbox_oldest_pending_age_seconds gauge',
            'hociatec_outbox_oldest_pending_age_seconds '.($metrics->oldestPendingAgeSeconds ?? 0),
            '# HELP hociatec_outbox_failed_events Failed outbox events awaiting retry.',
            '# TYPE hociatec_outbox_failed_events gauge',
            'hociatec_outbox_failed_events '.$metrics->failedEvents,
            '# HELP hociatec_outbox_stale_processing_events Outbox events stuck in processing.',
            '# TYPE hociatec_outbox_stale_processing_events gauge',
            'hociatec_outbox_stale_processing_events '.$metrics->staleProcessingEvents,
            '# HELP hociatec_outbox_dead_events Dead-lettered outbox events.',
            '# TYPE hociatec_outbox_dead_events gauge',
            'hociatec_outbox_dead_events '.$metrics->deadEvents,
        ];
    }
}
