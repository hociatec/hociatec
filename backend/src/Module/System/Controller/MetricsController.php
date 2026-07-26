<?php

declare(strict_types=1);

namespace App\Module\System\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metrics', name: 'app_metrics', methods: ['GET'])]
final readonly class MetricsController
{
    public function __construct(private Connection $connection)
    {
    }

    public function __invoke(): Response
    {
        $databaseUp = 0;
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $databaseUp = 1;
        } catch (\Throwable) {
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
}
