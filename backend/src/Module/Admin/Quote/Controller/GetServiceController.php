<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\ServiceRepository;
use App\Module\Quote\Service\QuoteFormatter;
use App\Shared\Http\ApiResponse;
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
#[IsGranted('ROLE_ADMIN')]
class GetServiceController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
    ) {
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
