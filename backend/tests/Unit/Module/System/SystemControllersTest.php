<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\System;

use App\Module\System\UI\Controller\HealthController;
use App\Module\System\UI\Controller\MetricsController;
use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Application\OutboxMetrics;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SystemControllersTest extends TestCase
{
    public function testHealthControllerReportsHealthyAndUnhealthyDatabase(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('fetchOne')->willReturn(1);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')->with('SELECT 1')->willReturn($result);

        $healthy = (new HealthController($connection))();
        $healthyPayload = json_decode((string) $healthy->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_OK, $healthy->getStatusCode());
        self::assertSame('ok', $healthyPayload['data']['health']);
        self::assertStringContainsString('no-store', (string) $healthy->headers->get('Cache-Control'));

        $failingConnection = $this->createMock(Connection::class);
        $failingConnection->expects(self::once())->method('executeQuery')->willThrowException(new DbalException('db down'));

        $unhealthy = (new HealthController($failingConnection))();
        $unhealthyPayload = json_decode((string) $unhealthy->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $unhealthy->getStatusCode());
        self::assertSame('error', $unhealthyPayload['details']['health']);
        self::assertFalse($unhealthyPayload['details']['checks']['database']);
    }

    public function testMetricsControllerCoversLocalhostSuccessAndEmptyTokenDenial(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('fetchOne')->willReturn(1);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')->with('SELECT 1')->willReturn($result);

        $local = (new MetricsController($connection, '', $this->outboxEvents()))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1'])
        );

        self::assertSame(Response::HTTP_OK, $local->getStatusCode());
        self::assertStringContainsString('hociatec_metrics_endpoint_up 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_observability_pipeline_info{format="prometheus",logs="json",request_id="enabled"} 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_database_up 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_pending_events 3', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_oldest_pending_age_seconds 42', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_failed_events 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_stale_processing_events 2', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_dead_events 4', (string) $local->getContent());

        $failingConnection = $this->createMock(Connection::class);
        $failingConnection->expects(self::once())->method('executeQuery')->willThrowException(new DbalException('db down'));
        $degraded = (new MetricsController($failingConnection, ''))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1'])
        );
        self::assertSame(Response::HTTP_OK, $degraded->getStatusCode());
        self::assertStringContainsString('hociatec_database_up 0', (string) $degraded->getContent());

        $denied = (new MetricsController($this->createMock(Connection::class), ''))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10'])
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $denied->getStatusCode());
    }

    private function outboxEvents(): OutboxEventStore
    {
        return new class implements OutboxEventStore {
            public function findDueForUpdate(int $limit): array
            {
                return [];
            }

            public function recoverStaleProcessing(\DateTimeImmutable $threshold): int
            {
                return 0;
            }

            public function metricsSnapshot(\DateTimeImmutable $staleProcessingThreshold): OutboxMetrics
            {
                return new OutboxMetrics(3, 42, 1, 2, 4);
            }

            public function purgeFinalizedBefore(\DateTimeImmutable $threshold): int
            {
                return 0;
            }
        };
    }
}
