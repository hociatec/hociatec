<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services', name: 'api_admin_services_list', methods: ['GET'])]
#[IsGranted('ROLE_QUOTES_MANAGER')]
class ListServicesController extends AbstractController
{
    public function __construct(
        private readonly ServiceOfferingRepositoryPort $serviceRepository,
        private readonly QuoteFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 100);
        $search = RequestQueryMapper::nullableString($request, 'q');
        $items = $this->serviceRepository->findForAdmin($search, $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($s) => $this->formatter->formatService($s), $items),
            $pagination->metadata($this->serviceRepository->countForAdmin($search)),
        );
    }
}
