<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Operations\Service\SupportOperationsService;
use App\Shared\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/support-requests')]
#[IsGranted('ROLE_ADMIN')]
final readonly class SupportOperationsController
{
    public function __construct(private SupportOperationsService $support)
    {
    }

    #[Route('', name: 'api_admin_operations_support_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success(['items' => $this->support->list()]);
    }

    #[Route('', name: 'api_admin_operations_support_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $item = $this->support->create($request->toArray());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created(['item' => $item]);
    }

    #[Route('/{id}', name: 'api_admin_operations_support_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $item = $this->support->update($id, $request->toArray());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['item' => $item]);
    }

    #[Route('/{id}/reply', name: 'api_admin_operations_support_reply', methods: ['POST'])]
    public function reply(int $id, Request $request): JsonResponse
    {
        try {
            $item = $this->support->reply($id, $request->toArray());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['sent' => true, 'item' => $item]);
    }
}
