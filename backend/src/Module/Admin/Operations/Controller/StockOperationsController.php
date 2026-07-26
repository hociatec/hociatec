<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\DTO\StockMovementInput;
use App\Module\Admin\Operations\DTO\UpdateLowStockThresholdInput;
use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Operations\Service\StockOperationsService;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations')]
#[IsGranted('ROLE_OPERATIONS')]
final class StockOperationsController extends AbstractController
{
    public function __construct(
        private readonly StockOperationsService $stock,
        private readonly DtoValidator $validator,
    ) {
    }

    #[Route('/stock-movements', name: 'api_admin_operations_stock_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success(['items' => $this->stock->list()]);
    }

    #[Route('/stock-movements', name: 'api_admin_operations_stock_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
            $input = StockMovementInput::fromArray($payload);
            $this->validator->validate($input);
            $item = $this->stock->create(
                $input->productId,
                $input->delta,
                $input->reason,
                $input->note,
                $this->currentAdmin(),
            );
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created(['item' => $item]);
    }

    #[Route('/products/{id}/low-stock-threshold', name: 'api_admin_operations_product_low_stock_threshold', methods: ['PATCH'])]
    public function updateThreshold(int $id, Request $request): JsonResponse
    {
        try {
            $input = UpdateLowStockThresholdInput::fromArray(\App\Shared\Http\JsonPayload::decode($request));
            $this->validator->validate($input);
            $product = $this->stock->updateThreshold($id, $input->threshold);
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['product' => $product]);
    }

    private function currentAdmin(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
