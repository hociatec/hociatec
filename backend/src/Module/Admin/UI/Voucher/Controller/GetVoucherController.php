<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Voucher\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers/{voucherId}', name: 'api_admin_vouchers_get', methods: ['GET'], requirements: ['voucherId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class GetVoucherController extends AbstractController
{
    public function __construct(private readonly VoucherRepository $vouchers)
    {
    }

    public function __invoke(int $voucherId): JsonResponse
    {
        $voucher = $this->vouchers->find($voucherId);
        if (null === $voucher) {
            return ApiResponse::error('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'voucher' => VoucherFormatter::formatVoucher($voucher),
        ]);
    }
}
