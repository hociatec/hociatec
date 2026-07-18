<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\User\Repository\UserRepository;
use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Module\Voucher\Service\VoucherManager;
use App\Module\Voucher\Service\VoucherNotificationEmailService;
use App\Shared\Http\ApiResponse;
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
        private readonly VoucherManager $voucherManager,
        private readonly VoucherNotificationEmailService $notifications,
        private readonly VoucherRepository $vouchers,
    ) {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $voucher = $this->voucherManager->create([
                'name' => trim((string) ($payload['name'] ?? '')),
                'code' => $this->normalizeCode($payload['code'] ?? $this->generateCode($user->getLastName())),
                'description' => isset($payload['description']) ? trim((string) $payload['description']) : null,
                'discountType' => trim((string) ($payload['discountType'] ?? Voucher::TYPE_FIXED_CENTS)),
                'discountValue' => (int) ($payload['discountValue'] ?? 0),
                'isActive' => (bool) ($payload['isActive'] ?? true),
                'startsAt' => $this->parseDate($payload['startsAt'] ?? null),
                'endsAt' => $this->parseDate($payload['endsAt'] ?? null),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $emailSent = false;
        $voucher
            ->setRecipientUserId($user->getId())
            ->setRecipientEmail($user->getEmail());

        if ((bool) ($payload['sendEmail'] ?? true)) {
            $this->notifications->sendCustomerVoucher($user, $voucher);
            $voucher->setSentAt(new \DateTimeImmutable());
            $emailSent = true;
        }

        $this->vouchers->getEntityManager()->persist($voucher);
        $this->vouchers->getEntityManager()->flush();

        return ApiResponse::created([
            'voucher' => VoucherFormatter::formatVoucher($voucher),
            'emailSent' => $emailSent,
        ]);
    }

    private function normalizeCode(mixed $value): string
    {
        return \is_string($value) ? mb_strtoupper(trim($value)) : '';
    }

    private function generateCode(string $seed): string
    {
        $base = (trim($seed) !== '' ? $seed : 'CLIENT');
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base) ?: 'CLIENT';
        $base = trim(strtoupper($base), '-');

        return substr($base, 0, 12) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
