<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Module\Admin\Application\Order\DTO\OrderStatusInput;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Writer\OrderStatusUpdater;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\ApiValidationException;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/status', name: 'api_admin_orders_update_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ORDERS_MANAGER')]
final class UpdateOrderStatusController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryPort $orders,
        private readonly OrderStatusUpdater $statusUpdater,
        private readonly DtoValidator $validator,
        private readonly OrderFormatter $orderFormatter,
    ) {
    }

    public function __invoke(int $orderId, Request $request): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
            $input = OrderStatusInput::fromArray($payload);
            $this->validator->validate($input);
            $actor = $this->getUser();
            $order = $this->statusUpdater->update($order, $input->status, $actor instanceof User ? $actor : null);
        } catch (\DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST, $exception->details);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::successItem('order', $this->orderFormatter->formatOrder($order));
    }
}
