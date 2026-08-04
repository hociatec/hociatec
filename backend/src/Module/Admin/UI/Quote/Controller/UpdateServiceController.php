<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Quote\Service\QuoteServiceFormMapper;
use App\Module\Admin\Application\Quote\Service\UpdateQuoteServiceHandler;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use App\Module\Quote\Infrastructure\Repository\ServiceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services/{id}', name: 'api_admin_services_update', methods: ['POST', 'PUT', 'PATCH'], requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class UpdateServiceController
{
    public function __construct(
        private ServiceRepository $repository,
        private QuoteServiceFormMapper $forms,
        private UpdateQuoteServiceHandler $updateService,
    ) {
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $service = $this->repository->find($id);
        if (!$service instanceof Service) {
            return ApiResponse::error('Service introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $service = $this->updateService->update($service, $this->forms->update($request, $service));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QuoteOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::success(QuoteFormatter::formatService($service), JsonResponse::HTTP_OK, 'Le service a bien été mis à jour.');
    }
}
