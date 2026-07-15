<?php

declare(strict_types=1);

namespace App\Module\Admin\Voucher\Controller;

use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Shared\Http\ApiResponse;
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
        if ($voucher === null) {
            return ApiResponse::error('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'voucher' => VoucherFormatter::formatVoucher($voucher),
        ]);
    }
}
