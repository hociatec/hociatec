<?php

declare(strict_types=1);

namespace App\Module\Admin\Order\Controller;

use App\Module\Admin\Order\DTO\OrderStatusInput;
use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Order\Service\OrderStatusUpdater;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/status', name: 'api_admin_orders_update_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateOrderStatusController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderStatusUpdater $statusUpdater,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $orderId, Request $request): JsonResponse
    {
        $order = $this->orders->find($orderId);
        if (!$order instanceof Order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
            $input = OrderStatusInput::fromArray($payload);
            $this->validator->validate($input);
            $actor = $this->getUser();
            $order = $this->statusUpdater->update($order, $input->status, $actor instanceof User ? $actor : null);
        } catch (\DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}
