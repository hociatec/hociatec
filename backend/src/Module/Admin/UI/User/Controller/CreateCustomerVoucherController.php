<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Module\Admin\Application\User\DTO\CustomerVoucherInput;
use App\Module\Admin\Application\User\Handler\CreateCustomerVoucherHandler as CreateCustomerVoucherForCustomerHandler;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\RequestPayloadMapper;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/vouchers', name: 'api_admin_customers_create_voucher', methods: ['POST'])]
#[IsGranted('ROLE_CUSTOMERS_MANAGER')]
final class CreateCustomerVoucherController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryPort $users,
        private readonly CreateCustomerVoucherForCustomerHandler $createVoucher,
        private readonly DtoValidator $validator,
        private readonly VoucherFormatter $formatter,
    ) {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = CustomerVoucherInput::fromArray($payload);
            $this->validator->validate($input);
            $created = $this->createVoucher->create($user, $input, [
                'name' => $input->name,
                'code' => RequestPayloadMapper::normalizedCode($input->code ?? RequestPayloadMapper::generatedCode($user->getLastName())),
                'description' => $input->description,
                'discountType' => $input->discountType,
                'discountValue' => $input->discountValue,
                'isActive' => $input->isActive,
                'startsAt' => RequestPayloadMapper::dateOrNull($input->startsAt),
                'endsAt' => RequestPayloadMapper::dateOrNull($input->endsAt),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created([
            'voucher' => $this->formatter->formatVoucher($created->voucher),
            'emailSent' => $created->emailSent,
        ]);
    }
}
