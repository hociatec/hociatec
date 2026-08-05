<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\PublicApi;

use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/services', name: 'api_public_services_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListServicesController extends AbstractController
{
    public function __construct(private readonly ServiceOfferingRepositoryPort $serviceRepository)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 20, 50);
        $services = $this->serviceRepository->findPaginated($pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(static fn ($s) => QuoteFormatter::formatService($s), $services),
            $pagination->metadata($this->serviceRepository->countAll()),
        );
    }
}
