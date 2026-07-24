<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Operations\Service\RefundOperationsService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/refunds')]
#[IsGranted('ROLE_ADMIN')]
final class RefundOperationsController extends AbstractController
{
    public function __construct(private readonly RefundOperationsService $refunds)
    {
    }

    #[Route('', name: 'api_admin_operations_refunds_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success(['items' => $this->refunds->list()]);
    }

    #[Route('', name: 'api_admin_operations_refunds_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $item = $this->refunds->create($request->toArray(), $this->currentAdmin());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created(['item' => $item]);
    }

    #[Route('/{id}', name: 'api_admin_operations_refunds_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $item = $this->refunds->update($id, $request->toArray());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['item' => $item]);
    }

    #[Route('/{id}/process-stripe', name: 'api_admin_operations_refunds_process_stripe', methods: ['POST'])]
    public function processStripe(int $id, Request $request): JsonResponse
    {
        try {
            $result = $this->refunds->processStripe($id, $request->toArray(), $this->currentAdmin());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success($result);
    }

    private function currentAdmin(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
