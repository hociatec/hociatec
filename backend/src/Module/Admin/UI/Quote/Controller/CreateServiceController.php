<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Quote\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Quote\Service\CreateQuoteServiceHandler;
use App\Module\Admin\Application\Quote\Service\QuoteServiceFormMapper;
use App\Module\Quote\Application\Service\QuoteFormatter;
use App\Module\Quote\Domain\Exception\QuoteOperationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/services', name: 'api_admin_services_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final readonly class CreateServiceController
{
    public function __construct(
        private QuoteServiceFormMapper $forms,
        private CreateQuoteServiceHandler $createService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $service = $this->createService->create($this->forms->create($request));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QuoteOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::created(QuoteFormatter::formatService($service), 'Le service a bien été créé.');
    }
}
