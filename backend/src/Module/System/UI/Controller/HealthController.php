<?php

declare(strict_types=1);

namespace App\Module\System\UI\Controller;

use App\Shared\Infrastructure\Http\ApiResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET', 'HEAD'])]
    #[Route('/api/health/readiness', name: 'api_health_readiness', methods: ['GET', 'HEAD'])]
    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
        ];

        $healthy = !in_array(false, $checks, true);
        $data = [
            'health' => $healthy ? 'ok' : 'error',
            'checks' => $checks,
            'time' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
        $response = $healthy
            ? ApiResponse::success($data)
            : ApiResponse::error('Service indisponible.', Response::HTTP_SERVICE_UNAVAILABLE, $data);

        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }

    #[Route('/api/health/liveness', name: 'api_health_liveness', methods: ['GET', 'HEAD'])]
    public function liveness(): JsonResponse
    {
        $response = ApiResponse::success([
            'health' => 'ok',
            'checks' => [],
            'time' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }

    private function checkDatabase(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();

            return true;
        } catch (DbalException) {
            return false;
        }
    }
}
