<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metrics', name: 'app_metrics', methods: ['GET'])]
final readonly class MetricsController
{
    public function __construct(private Connection $connection, private string $metricsToken)
    {
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

        $body = implode("\n", [
            '# HELP hociatec_info Application information.',
            '# TYPE hociatec_info gauge',
            'hociatec_info{php_version="'.PHP_VERSION.'"} 1',
            '# HELP hociatec_database_up Database availability.',
            '# TYPE hociatec_database_up gauge',
            'hociatec_database_up '.$databaseUp,
            '',
        ]);

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
