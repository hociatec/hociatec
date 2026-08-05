<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Module\Admin\Application\Operations\DTO\RefundCreateInput;
use App\Module\Admin\Application\Operations\DTO\RefundProcessInput;
use App\Module\Admin\Application\Operations\DTO\RefundUpdateInput;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Workflow\RefundOperationsService;
use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/refunds')]
#[IsGranted('ROLE_OPERATIONS')]
final class RefundOperationsController extends AbstractController
{
    public function __construct(private readonly RefundOperationsService $refunds, private readonly DtoValidator $validator)
    {
    }

    #[Route('', name: 'api_admin_operations_refunds_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::successItem('items', $this->refunds->list());
    }

    #[Route('', name: 'api_admin_operations_refunds_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, RefundCreateInput::class);
            $this->validator->validate($input);
            $item = $this->refunds->create(new RefundCreateData($input->orderId, $input->amountCents, $input->reason, $input->internalNotes, $input->paymentId, $input->currencyCode), $this->currentAdmin());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::createdItem('item', $item);
    }

    #[Route('/{id}', name: 'api_admin_operations_refunds_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, RefundUpdateInput::class);
            $this->validator->validate($input);
            $item = $this->refunds->update($id, new RefundUpdateData($input->status, $input->stripeRefundId, $input->internalNotes));
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::successItem('item', $item);
    }

    #[Route('/{id}/process-stripe', name: 'api_admin_operations_refunds_process_stripe', methods: ['POST'])]
    public function processStripe(int $id, Request $request): JsonResponse
    {
        try {
            $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, RefundProcessInput::class);
            $this->validator->validate($input);
            $result = $this->refunds->processStripe($id, new RefundProcessData($input->confirmation, $input->paymentIntentId), $this->currentAdmin());
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
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
