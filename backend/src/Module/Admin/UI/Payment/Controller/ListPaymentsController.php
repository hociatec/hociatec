<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Payment\Controller;

use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
use App\Module\Order\Application\Workflow\StripeCheckoutSessionSyncService;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/payments', name: 'api_admin_payments_list', methods: ['GET'])]
#[IsGranted('ROLE_PAYMENTS_MANAGER')]
final class ListPaymentsController extends AbstractController
{
    public function __construct(
        private readonly OrderCheckoutSessionRepositoryPort $payments,
        private readonly StripeCheckoutSessionSyncService $stripeSync,
        private readonly AdminPaymentFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = RequestQueryMapper::pagination($request);
        $statusFilter = RequestQueryMapper::nullableString($request, 'status');
        $query = RequestQueryMapper::string($request, 'q');

        if ('' === $query && (null === $statusFilter || in_array($statusFilter, ['all', OrderCheckoutSession::STATUS_FAILED, OrderCheckoutSession::STATUS_OPEN], true))) {
            $this->stripeSync->syncRecentOpenPayments();
        }

        $items = $this->payments->findForAdminList($statusFilter, $query, $pagination->perPage, $pagination->offset());
        $total = $this->payments->countForAdminList($statusFilter, $query);

        return ApiResponse::paginated(array_map($this->formatter->summary(...), $items), $pagination->metadata($total));
    }
}
