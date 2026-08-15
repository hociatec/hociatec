<?php

declare(strict_types=1);

namespace App\Module\Service\UI\Controller\PublicApi;

use App\Module\Service\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Service\Application\Projection\ServiceFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/services/{id}', name: 'api_public_services_get', methods: ['GET'], requirements: ['id' => '\d+'])]
#[RateLimited('public_api')]
class GetServiceController extends AbstractController
{
    public function __construct(
        private readonly ServiceOfferingRepositoryPort $serviceRepository,
        private readonly ServiceFormatter $formatter,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);

        if (null === $service) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($this->formatter->format($service));
    }
}
