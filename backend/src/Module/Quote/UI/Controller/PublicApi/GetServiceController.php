<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\PublicApi;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Infrastructure\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/services/{id}', name: 'api_public_services_get', methods: ['GET'], requirements: ['id' => '\d+'])]
#[RateLimited('public_api')]
class GetServiceController extends AbstractController
{
    public function __construct(private readonly ServiceRepository $serviceRepository)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);

        if (null === $service) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success(QuoteFormatter::formatService($service));
    }
}
