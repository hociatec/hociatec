<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\ApiValidationException;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Order\Application\DTO\DeliveryInput;
use App\Module\Order\Application\Service\OrderDeliveryUpdater;
use App\Module\Order\Application\Service\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders/{orderId}/delivery', name: 'api_admin_orders_update_delivery', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateOrderDeliveryController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderDeliveryUpdater $delivery,
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
            $actor = $this->getUser();
            $input = DeliveryInput::fromArray(\App\Infrastructure\Http\JsonPayload::decode($request));
            $this->validator->validate($input);
            $order = $this->delivery->update(
                $order,
                $input,
                $actor instanceof User ? $actor : null,
            );
        } catch (ApiValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST, $exception->details);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['order' => OrderFormatter::formatOrder($order)]);
    }
}
