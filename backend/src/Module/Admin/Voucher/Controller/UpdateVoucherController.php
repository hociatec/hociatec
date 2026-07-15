<?php

declare(strict_types=1);

namespace App\Module\Admin\Voucher\Controller;

use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $voucherId, Request $request): JsonResponse
    {
        $voucher = $this->vouchers->find($voucherId);
        if ($voucher === null) {
            return ApiResponse::error('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $code = $this->normalizeCode($payload['code'] ?? null);
        $discountType = trim((string) ($payload['discountType'] ?? ''));
        $discountValue = (int) ($payload['discountValue'] ?? 0);

        if ($name === '' || $code === '' || $discountType === '') {
            return ApiResponse::error('Champs obligatoires manquants.', Response::HTTP_BAD_REQUEST);
        }

        $existingVoucher = $this->vouchers->findOneByCode($code);
        if ($existingVoucher !== null && $existingVoucher->getId() !== $voucher->getId()) {
            return ApiResponse::error('Ce code existe déjà.', Response::HTTP_BAD_REQUEST);
        }

        if (!\in_array($discountType, [Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED_CENTS], true)) {
            return ApiResponse::error('Type de remise invalide.', Response::HTTP_BAD_REQUEST);
        }

        if ($discountValue <= 0) {
            return ApiResponse::error('La valeur de remise doit être supérieure à zéro.', Response::HTTP_BAD_REQUEST);
        }

        $voucher
            ->setName($name)
            ->setCode($code)
            ->setDescription(isset($payload['description']) ? trim((string) $payload['description']) : null)
            ->setDiscountType($discountType)
            ->setDiscountValue($discountValue)
            ->setIsActive((bool) ($payload['isActive'] ?? true))
            ->setStartsAt($this->parseDate($payload['startsAt'] ?? null))
            ->setEndsAt($this->parseDate($payload['endsAt'] ?? null));

        $this->entityManager->persist($voucher);
        $this->entityManager->flush();

        return ApiResponse::success([
            'voucher' => VoucherFormatter::formatVoucher($voucher),
        ]);
    }

    private function normalizeCode(mixed $value): string
    {
        return \is_string($value) ? mb_strtoupper(trim($value)) : '';
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
