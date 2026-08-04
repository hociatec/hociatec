<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Module\Outbox\Application\OutboxEventStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metrics', name: 'app_metrics', methods: ['GET'])]
final readonly class MetricsController
{
    public function __construct(
        private Connection $connection,
        private string $metricsToken,
        private ?OutboxEventStore $outboxEvents = null,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->isAllowed($request)) {
            return new Response('Forbidden', Response::HTTP_FORBIDDEN, ['Cache-Control' => 'no-store']);
        }

        $databaseUp = 0;
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $databaseUp = 1;
        } catch (DbalException) {
        }

        $lines = [
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
        ];

        if (null !== $this->outboxEvents) {
            $metrics = $this->outboxEvents->metricsSnapshot(new \DateTimeImmutable('-15 minutes'));
            $lines = array_merge($lines, [
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
            ]);
        }

        $lines[] = '';
        $body = implode("\n", $lines);

        return new Response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function isAllowed(Request $request): bool
    {
        $clientIp = $request->getClientIp();
        if (in_array($clientIp, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        return '' !== $this->metricsToken
            && hash_equals($this->metricsToken, (string) $request->headers->get('X-Metrics-Token', ''));
    }
}
