<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Operations\Service\FulfillmentOperationsService;
use App\Module\Order\DTO\DeliveryInput;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/fulfillment')]
#[IsGranted('ROLE_OPERATIONS')]
final class FulfillmentOperationsController extends AbstractController
{
    public function __construct(private readonly FulfillmentOperationsService $fulfillment, private readonly DtoValidator $validator)
    {
    }

    #[Route('/orders', name: 'api_admin_operations_fulfillment_orders', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success(['items' => $this->fulfillment->queue()]);
    }

    #[Route('/orders/{id}/ship', name: 'api_admin_operations_fulfillment_ship', methods: ['PATCH'])]
    public function ship(int $id, Request $request): JsonResponse
    {
        try {
            $input = DeliveryInput::fromArray(\App\Shared\Http\JsonPayload::decode($request));
            $this->validator->validate($input);
            $order = $this->fulfillment->ship($id, $input, $this->currentAdmin());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['order' => $order]);
    }

    private function currentAdmin(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
