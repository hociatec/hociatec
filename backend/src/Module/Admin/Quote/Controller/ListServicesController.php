<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\ServiceRepository;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/quotes/services', name: 'api_admin_quotes_services_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListServicesController extends AbstractController
{
    public function __construct(private readonly ServiceRepository $serviceRepository)
    {
    }

    public function __invoke(): JsonResponse
    {
        $items = $this->serviceRepository->findBy([], ['title' => 'ASC']);

        return ApiResponse::success([
            'items' => array_map(static fn ($s) => QuoteFormatter::formatService($s), $items),
        ]);
    }
}

