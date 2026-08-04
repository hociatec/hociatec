<?php

declare(strict_types=1);

namespace App\Module\Voucher\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Service\VoucherFormatter;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/vouchers/me', name: 'api_vouchers_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListMyVouchersController extends AbstractController
{
    public function __construct(private readonly VoucherRepository $vouchers)
    {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::success([
            'items' => array_map(
                static fn ($voucher): array => VoucherFormatter::formatVoucher($voucher),
                $this->vouchers->findByRecipientUserId((int) $user->getId()),
            ),
        ]);
    }
}
