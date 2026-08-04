<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Infrastructure\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services', name: 'api_admin_services_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListServicesController extends AbstractController
{
    public function __construct(private readonly ServiceRepository $serviceRepository)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $items = $this->serviceRepository->findBy([], ['title' => 'ASC'], $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(static fn ($s) => QuoteFormatter::formatService($s), $items),
            $pagination->metadata($this->serviceRepository->count([])),
        );
    }
}
