<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Order\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Order\Application\Service\OrderFormatter;
use App\Module\Order\Application\Service\OrderIssueInspector;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders', name: 'api_admin_orders_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListOrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderEventRepository $events,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $health = $request->query->get('health');
        $pagination = Pagination::fromRequest($request, 25, 100);

        $qb = $this->orders->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if (is_string($status) && '' !== $status && 'all' !== $status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        if ('issues' === $health) {
            $qb
                ->leftJoin('App\Module\Order\Domain\Entity\OrderEvent', 'e', 'WITH', 'e.order = o')
                ->andWhere(
                    $qb->expr()->orX(
                        'o.invoicePdfPath IS NULL',
                        'o.invoiceXmlPath IS NULL',
                        'o.orderCreatedEmailSentAt IS NULL',
                        $qb->expr()->in('e.type', ':issueTypes'),
                    )
                )
                ->setParameter('issueTypes', [
                    'email_failed',
                    'invoice_generation_failed',
                    'post_processing_failed',
                ])
                ->groupBy('o.id');
        }

        $countQb = clone $qb;
        $countQb->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select('COUNT(DISTINCT o.id)')
            ->setFirstResult(null)
            ->setMaxResults(null);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb
            ->setFirstResult($pagination->offset())
            ->setMaxResults($pagination->perPage);
        /** @var list<Order> $orders */
        $orders = $qb->getQuery()->getResult();
        $issueEventsByOrderId = $this->events->findIssueEventsGroupedByOrders($orders);

        $items = array_map(
            static function (Order $order) use ($issueEventsByOrderId): array {
                $issueReasons = OrderIssueInspector::getOperationalIssues(
                    $order,
                    $issueEventsByOrderId[$order->getId() ?? 0] ?? [],
                );

                return OrderFormatter::formatOrder($order, [], [
                    'hasIssues' => [] !== $issueReasons,
                    'issueReasons' => $issueReasons,
                ]);
            },
            $orders,
        );

        return ApiResponse::paginated($items, $pagination->metadata($total));
    }
}
