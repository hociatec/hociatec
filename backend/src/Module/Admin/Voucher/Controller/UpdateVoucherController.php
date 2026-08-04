<?php

declare(strict_types=1);

namespace App\Module\Admin\Voucher\Controller;

use App\Module\Admin\Voucher\DTO\VoucherInput;
use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Module\Voucher\Service\VoucherManager;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers/{voucherId}', name: 'api_admin_vouchers_update', methods: ['PUT'], requirements: ['voucherId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateVoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherRepository $vouchers,
        private readonly VoucherManager $voucherManager,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $voucherId, Request $request): JsonResponse
    {
        $voucher = $this->vouchers->find($voucherId);
        if (null === $voucher) {
            return ApiResponse::error('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (\Exception) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = VoucherInput::fromArray($payload);
        $this->validator->validate($input);
        try {
            $voucher = $this->voucherManager->update($voucher, $this->toPayload($input));
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success([
            'voucher' => VoucherFormatter::formatVoucher($voucher),
        ], JsonResponse::HTTP_OK, 'Le bon de réduction a bien été mis à jour.');
    }

    /** @return array{name: string, code: string, description: string|null, discountType: string, discountValue: int, isActive: bool, startsAt: \DateTimeImmutable|null, endsAt: \DateTimeImmutable|null} */
    private function toPayload(VoucherInput $input): array
    {
        return ['name' => $input->name, 'code' => $input->code, 'description' => $input->description, 'discountType' => $input->discountType, 'discountValue' => $input->discountValue, 'isActive' => $input->isActive, 'startsAt' => $this->parseDate($input->startsAt), 'endsAt' => $this->parseDate($input->endsAt)];
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
