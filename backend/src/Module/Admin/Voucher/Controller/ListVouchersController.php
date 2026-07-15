<?php

declare(strict_types=1);

namespace App\Module\Admin\Voucher\Controller;

use App\Module\Voucher\Repository\VoucherRepository;
use App\Module\Voucher\Service\VoucherFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers', name: 'api_admin_vouchers_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListVouchersController extends AbstractController
{
    public function __construct(private readonly VoucherRepository $vouchers)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(
                static fn ($voucher) => VoucherFormatter::formatVoucher($voucher),
                $this->vouchers->findBy([], ['updatedAt' => 'DESC']),
            ),
        ]);
    }
}
