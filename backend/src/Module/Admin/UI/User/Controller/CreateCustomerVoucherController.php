<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Module\Admin\Application\User\DTO\CustomerVoucherInput;
use App\Module\Admin\Application\User\Service\CreateCustomerVoucherHandler as CreateCustomerVoucherForCustomerHandler;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/vouchers', name: 'api_admin_customers_create_voucher', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateCustomerVoucherController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly CreateCustomerVoucherForCustomerHandler $createVoucher,
        private readonly DtoValidator $validator,
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
                'code' => $this->normalizeCode($input->code ?? $this->generateCode($user->getLastName())),
                'description' => $input->description,
                'discountType' => $input->discountType,
                'discountValue' => $input->discountValue,
                'isActive' => $input->isActive,
                'startsAt' => $this->parseDate($input->startsAt),
                'endsAt' => $this->parseDate($input->endsAt),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created([
            'voucher' => VoucherFormatter::formatVoucher($created->voucher),
            'emailSent' => $created->emailSent,
        ]);
    }

    private function normalizeCode(mixed $value): string
    {
        return \is_string($value) ? mb_strtoupper(trim($value)) : '';
    }

    private function generateCode(string $seed): string
    {
        $base = ('' !== trim($seed) ? $seed : 'CLIENT');
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base) ?: 'CLIENT';
        $base = trim(strtoupper($base), '-');

        return substr($base, 0, 12).'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }
}
