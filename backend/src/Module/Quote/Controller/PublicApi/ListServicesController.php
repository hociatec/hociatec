<?php

declare(strict_types=1);

namespace App\Module\Quote\Controller\PublicApi;

use App\Module\Quote\Repository\ServiceRepository;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/services', name: 'api_public_services_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListServicesController extends AbstractController
{
    public function __construct(private readonly ServiceRepository $serviceRepository)
    {
    }

    public function __invoke(): JsonResponse
    {
        $services = $this->serviceRepository->findBy([], ['title' => 'ASC']);

        return ApiResponse::success([
            'items' => array_map(static fn ($s) => QuoteFormatter::formatService($s), $services),
        ]);
    }
}
