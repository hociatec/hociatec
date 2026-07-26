<?php

declare(strict_types=1);

namespace App\Module\Admin\Quote\Controller;

use App\Module\Quote\Repository\ServiceRepository;
use App\Shared\Http\ApiResponse;
use App\Module\Quote\Service\QuoteCatalogManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services/{id}', name: 'api_admin_services_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteServiceController extends AbstractController
{
    public function __construct(
        private readonly QuoteCatalogManager $catalog,
        private readonly ServiceRepository $serviceRepository,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (null === $service) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->catalog->delete($service);

        return ApiResponse::success(['deleted' => true]);
    }
}
