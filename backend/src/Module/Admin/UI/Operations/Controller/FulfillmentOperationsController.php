<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Service\FulfillmentOperationsService;
use App\Module\Order\Application\DTO\DeliveryInput;
use App\Module\User\Domain\Entity\User;
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
        return ApiResponse::successItem('items', $this->fulfillment->queue());
    }

    #[Route('/orders/{id}/ship', name: 'api_admin_operations_fulfillment_ship', methods: ['PATCH'])]
    public function ship(int $id, Request $request): JsonResponse
    {
        try {
            $input = \App\Infrastructure\Http\JsonRequestInput::decode($request, DeliveryInput::class);
            $this->validator->validate($input);
            $order = $this->fulfillment->ship($id, $input, $this->currentAdmin());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::successItem('order', $order);
    }

    private function currentAdmin(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
