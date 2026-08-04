<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Voucher\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Voucher\Application\Service\VoucherManager;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers/{voucherId}', name: 'api_admin_vouchers_delete', methods: ['DELETE'], requirements: ['voucherId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteVoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherRepository $vouchers,
        private readonly VoucherManager $manager,
    ) {
    }

    public function __invoke(int $voucherId): JsonResponse
    {
        $voucher = $this->vouchers->find($voucherId);
        if (null === $voucher) {
            return ApiResponse::error('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->manager->delete($voucher);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'Le bon de réduction a bien été supprimé.');
    }
}
