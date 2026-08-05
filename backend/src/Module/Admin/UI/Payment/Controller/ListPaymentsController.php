<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Payment\Controller;

use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
use App\Module\Order\Application\Workflow\StripeCheckoutSessionSyncService;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/payments', name: 'api_admin_payments_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
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
        $status = $request->query->get('status');
        $query = trim((string) $request->query->get('q', ''));

        if ('' === $query && (!is_string($status) || '' === $status || in_array($status, ['all', OrderCheckoutSession::STATUS_FAILED, OrderCheckoutSession::STATUS_OPEN], true))) {
            $this->stripeSync->syncRecentOpenPayments();
        }

        $qb = $this->payments->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if (is_string($status) && '' !== $status && 'all' !== $status) {
            $qb->andWhere('p.lifecycle.status = :status')->setParameter('status', $status);
        }

        if ('' !== $query) {
            $qb->andWhere('p.customerEmail LIKE :q OR p.customerFullName LIKE :q OR p.payment.stripeSessionId LIKE :q OR p.payment.stripePaymentIntentId LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        /** @var list<OrderCheckoutSession> $items */
        $items = $qb->getQuery()->getResult();

        return ApiResponse::success([
            'items' => array_map($this->formatter->summary(...), $items),
        ]);
    }
}
