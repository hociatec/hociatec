<?php

declare(strict_types=1);

namespace App\Module\System\Controller;

use App\Shared\Http\ApiResponse;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/health', name: 'api_health', methods: ['GET', 'HEAD'])]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(): JsonResponse
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

    private function checkDatabase(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
