<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\System;

use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\Outbox\Application\OutboxMetrics;
use App\Module\System\Application\Provider\PrometheusMetricContractProvider;
use App\Module\System\UI\Controller\DownloadLatestIosAppController;
use App\Module\System\UI\Controller\HealthController;
use App\Module\System\UI\Controller\LatestIosAltStoreSourceController;
use App\Module\System\UI\Controller\MetricsController;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SystemControllersTest extends TestCase
{
    public function testHealthControllerReportsHealthyLivenessAndUnhealthyReadiness(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('fetchOne')->willReturn(1);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')->with('SELECT 1')->willReturn($result);

        $controller = new HealthController($connection);
        $liveness = $controller->liveness();
        $livenessPayload = json_decode((string) $liveness->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_OK, $liveness->getStatusCode());
        self::assertSame('ok', $livenessPayload['data']['health']);
        self::assertSame([], $livenessPayload['data']['checks']);

        $healthy = $controller->readiness();
        $healthyPayload = json_decode((string) $healthy->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_OK, $healthy->getStatusCode());
        self::assertSame('ok', $healthyPayload['data']['health']);
        self::assertStringContainsString('no-store', (string) $healthy->headers->get('Cache-Control'));

        $failingConnection = $this->createMock(Connection::class);
        $failingConnection->expects(self::once())->method('executeQuery')->willThrowException(new DbalException('db down'));

        $unhealthy = (new HealthController($failingConnection))->readiness();
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

        $local = (new MetricsController($connection, '', new PrometheusMetricContractProvider(), $this->outboxEvents()))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1'])
        );

        self::assertSame(Response::HTTP_OK, $local->getStatusCode());
        self::assertStringContainsString('hociatec_metrics_endpoint_up 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_observability_pipeline_info{format="prometheus",logs="json",request_id="enabled"} 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_database_up 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_http_request_duration_seconds_count 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_http_responses_total{status_class="4xx"} 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_http_responses_total{status_class="5xx"} 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_payment_failed_total 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_webhook_failures_total 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_pdf_generation_duration_seconds_count 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_email_failures_total 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_sql_slow_queries_total 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_backup_failed_total 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_admin_sensitive_actions_total 0', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_pending_events 3', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_oldest_pending_age_seconds 42', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_failed_events 1', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_stale_processing_events 2', (string) $local->getContent());
        self::assertStringContainsString('hociatec_outbox_dead_events 4', (string) $local->getContent());

        $failingConnection = $this->createMock(Connection::class);
        $failingConnection->expects(self::once())->method('executeQuery')->willThrowException(new DbalException('db down'));
        $degraded = (new MetricsController($failingConnection, '', new PrometheusMetricContractProvider()))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1'])
        );
        self::assertSame(Response::HTTP_OK, $degraded->getStatusCode());
        self::assertStringContainsString('hociatec_database_up 0', (string) $degraded->getContent());

        $denied = (new MetricsController($this->createMock(Connection::class), '', new PrometheusMetricContractProvider()))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10'])
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $denied->getStatusCode());
    }

    public function testDownloadLatestIosAppControllerReturnsAttachmentFromPublishedSource(): void
    {
        $sourceResponse = $this->createMock(ResponseInterface::class);
        $sourceResponse->expects(self::once())->method('toArray')->with(false)->willReturn([
            'apps' => [[
                'versions' => [[
                    'downloadURL' => 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-v1.0.1-b2/hociatec-altstore-v1.0.1-b2.ipa',
                ]],
            ]],
        ]);

        $upstream = $this->createMock(ResponseInterface::class);
        $upstream->expects(self::once())->method('getContent')->willReturn('ipa-bytes');
        $upstream->expects(self::once())->method('getHeaders')->with(false)->willReturn([
            'content-type' => ['application/octet-stream'],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::exactly(2))
            ->method('request')
            ->willReturnCallback(static function (string $method, string $url) use ($sourceResponse, $upstream) {
                if ('https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json' === $url) {
                    return $sourceResponse;
                }

                if ('https://github.com/hociatec/hociatec-downloads/releases/download/ios-v1.0.1-b2/hociatec-altstore-v1.0.1-b2.ipa' === $url) {
                    return $upstream;
                }

                throw new \RuntimeException(sprintf('Unexpected URL %s', $url));
            });

        $response = (new DownloadLatestIosAppController($httpClient, new AttachmentResponseFactory()))();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('ipa-bytes', $response->getContent());
        self::assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('hociatec-altstore-v1.0.1-b2.ipa', (string) $response->headers->get('Content-Disposition'));
    }

    public function testDownloadLatestIosAppControllerReturnsNotFoundWhenSourceIsMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())->method('request')->willThrowException(new class('down') extends \RuntimeException implements TransportExceptionInterface {
        });

        $response = (new DownloadLatestIosAppController($httpClient, new AttachmentResponseFactory()))();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('Téléchargement iPhone indisponible.', $payload['message']);
    }

    public function testLatestIosAltStoreSourceControllerReturnsPublishedJson(): void
    {
        $upstream = $this->createMock(ResponseInterface::class);
        $upstream->expects(self::once())->method('getContent')->willReturn('{"apps":[{"versions":[{"version":"1.0.1"}]}]}');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json', self::isArray())
            ->willReturn($upstream);

        $response = (new LatestIosAltStoreSourceController($httpClient))();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('{"apps":[{"versions":[{"version":"1.0.1"}]}]}', $response->getContent());
        self::assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
    }

    public function testLatestIosAltStoreSourceControllerReturnsBadGatewayWhenUpstreamFails(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())->method('request')->willThrowException(new class('down') extends \RuntimeException implements TransportExceptionInterface {
        });

        $response = (new LatestIosAltStoreSourceController($httpClient))();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
        self::assertSame('Source AltStore iPhone indisponible.', $payload['message']);
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
