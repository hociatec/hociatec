<?php

declare(strict_types=1);

namespace App\Module\Voucher\UI\Controller;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/vouchers/me', name: 'api_vouchers_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ListMyVouchersController extends AbstractController
{
    public function __construct(
        private readonly VoucherRepositoryPort $vouchers,
        private readonly VoucherFormatter $formatter,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();
        $userId = (int) $user->getId();

        return ApiResponse::paginated(
            array_map(
                fn ($voucher): array => $this->formatter->formatVoucher($voucher),
                $this->vouchers->findByRecipientUserId($userId, $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($this->vouchers->countByRecipientUserId($userId)),
        );
    }
}
