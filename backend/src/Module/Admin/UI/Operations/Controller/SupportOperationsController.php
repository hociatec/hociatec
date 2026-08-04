<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Operations\DTO\SupportCreateInput;
use App\Module\Admin\Application\Operations\DTO\SupportReplyInput;
use App\Module\Admin\Application\Operations\DTO\SupportUpdateInput;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Service\SupportOperationsService;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\DTO\SupportUpdateData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/support-requests')]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class SupportOperationsController
{
    public function __construct(private SupportOperationsService $support, private DtoValidator $validator)
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
            $input = SupportCreateInput::fromArray(\App\Infrastructure\Http\JsonPayload::decode($request));
            $this->validator->validate($input);
            $item = $this->support->create(new SupportCreateData($input->customerId, $input->subject, $input->reason, $input->message, $input->internalNotes, $input->orderId));
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created(['item' => $item]);
    }

    #[Route('/{id}', name: 'api_admin_operations_support_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $input = SupportUpdateInput::fromArray(\App\Infrastructure\Http\JsonPayload::decode($request));
            $this->validator->validate($input);
            $item = $this->support->update($id, new SupportUpdateData($input->status, $input->internalNotes, $input->subject));
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['item' => $item]);
    }

    #[Route('/{id}/reply', name: 'api_admin_operations_support_reply', methods: ['POST'])]
    public function reply(int $id, Request $request): JsonResponse
    {
        try {
            $input = SupportReplyInput::fromArray(\App\Infrastructure\Http\JsonPayload::decode($request));
            $this->validator->validate($input);
            $item = $this->support->reply($id, new SupportReplyData($input->message, $input->subject, $input->status));
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['sent' => true, 'item' => $item]);
    }
}
