<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Module\Outbox\Application\OutboxEventStore;
use App\Module\System\Application\Provider\PrometheusMetricContractProvider;
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
        private PrometheusMetricContractProvider $metrics,
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

        $lines = $this->metrics->baseLines($databaseUp);

        if (null !== $this->outboxEvents) {
            $outbox = $this->outboxEvents->metricsSnapshot(new \DateTimeImmutable('-15 minutes'));
            $lines = array_merge($lines, $this->metrics->outboxLines($outbox));
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
        if (in_array((string) $clientIp, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        if ('' === $this->metricsToken) {
            return false;
        }

        return hash_equals($this->metricsToken, (string) $request->headers->get('X-Metrics-Token', ''));
    }
}
