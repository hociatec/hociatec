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

#[Route('/api/admin/vouchers', name: 'api_admin_vouchers_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateVoucherController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VoucherRepository $vouchers,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
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

        if ($this->vouchers->findOneByCode($code) !== null) {
            return ApiResponse::error('Ce code existe déjà.', Response::HTTP_BAD_REQUEST);
        }

        if (!\in_array($discountType, [Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED_CENTS], true)) {
            return ApiResponse::error('Type de remise invalide.', Response::HTTP_BAD_REQUEST);
        }

        if ($discountValue <= 0) {
            return ApiResponse::error('La valeur de remise doit être supérieure à zéro.', Response::HTTP_BAD_REQUEST);
        }

        $voucher = new Voucher($name, $code, $discountType, $discountValue);
        $voucher
            ->setDescription(isset($payload['description']) ? trim((string) $payload['description']) : null)
            ->setIsActive((bool) ($payload['isActive'] ?? true))
            ->setStartsAt($this->parseDate($payload['startsAt'] ?? null))
            ->setEndsAt($this->parseDate($payload['endsAt'] ?? null));

        $this->entityManager->persist($voucher);
        $this->entityManager->flush();

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
