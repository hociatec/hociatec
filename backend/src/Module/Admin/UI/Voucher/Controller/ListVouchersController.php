<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Voucher\Controller;

use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers', name: 'api_admin_vouchers_list', methods: ['GET'])]
#[IsGranted('ROLE_VOUCHERS_MANAGER')]
final class ListVouchersController extends AbstractController
{
    public function __construct(
        private readonly VoucherRepositoryPort $vouchers,
        private readonly VoucherFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request, 10, 50);

        return ApiResponse::paginated(
            array_map(
                fn ($voucher) => $this->formatter->formatVoucher($voucher),
                $this->vouchers->findBy([], ['updatedAt' => 'DESC'], $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->vouchers->count([])),
        );
    }
}
