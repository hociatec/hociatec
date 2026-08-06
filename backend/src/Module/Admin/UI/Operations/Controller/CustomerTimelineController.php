<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Provider\CustomerTimelineProvider;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/customers/{id}/timeline', name: 'api_admin_operations_customer_timeline', methods: ['GET'])]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class CustomerTimelineController
{
    public function __construct(private CustomerTimelineProvider $timeline)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        try {
            $pagination = RequestQueryMapper::pagination($request, 10, 50);
            $items = $this->timeline->provide($id);

            return ApiResponse::paginated(
                array_slice($items, $pagination->offset(), $pagination->perPage),
                $pagination->metadata(count($items)),
            );
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }
}
