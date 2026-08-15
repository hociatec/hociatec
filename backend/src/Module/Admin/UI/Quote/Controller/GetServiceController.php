<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Module\Service\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Service\Application\Projection\ServiceFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(
    '/api/admin/services/{id}',
    name: 'api_admin_services_get',
    methods: ['GET'],
    requirements: ['id' => '\d+']
)]
#[IsGranted('ROLE_QUOTES_MANAGER')]
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
