<?php

declare(strict_types=1);

namespace App\Module\Admin\Voucher\Controller;

use App\Module\Voucher\Service\VoucherFormatter;
use App\Module\Voucher\Service\VoucherManager;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers', name: 'api_admin_vouchers_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateVoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherManager $voucherManager,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $voucher = $this->voucherManager->create([
                'name' => trim((string) ($payload['name'] ?? '')),
                'code' => $this->normalizeCode($payload['code'] ?? null),
                'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
                'discountType' => trim((string) ($payload['discountType'] ?? '')),
                'discountValue' => (int) ($payload['discountValue'] ?? 0),
                'isActive' => (bool) ($payload['isActive'] ?? true),
                'startsAt' => $this->parseDate($payload['startsAt'] ?? null),
                'endsAt' => $this->parseDate($payload['endsAt'] ?? null),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created([
            'voucher' => VoucherFormatter::formatVoucher($voucher),
        ]);
    }

    private function normalizeCode(mixed $value): string
    {
        return \is_string($value) ? mb_strtoupper(trim($value)) : '';
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
