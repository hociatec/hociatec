<?php

declare(strict_types=1);

namespace App\Module\Service\UI\Controller\PublicApi;

use App\Module\Service\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Service\Application\Projection\ServiceFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/services', name: 'api_public_services_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListServicesController extends AbstractController
{
    public function __construct(
        private readonly ServiceOfferingRepositoryPort $serviceRepository,
        private readonly ServiceFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 20, 50);
        $search = RequestQueryMapper::nullableString($request, 'q');
        $services = $this->serviceRepository->findPublic($search, $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($service) => $this->formatter->format($service), $services),
            $pagination->metadata($this->serviceRepository->countPublic($search)),
        );
    }
}
