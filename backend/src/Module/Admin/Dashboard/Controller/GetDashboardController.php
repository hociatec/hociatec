<?php

declare(strict_types=1);

namespace App\Module\Admin\Dashboard\Controller;

use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Entity\OrderEvent;
use App\Module\Order\Repository\OrderEventRepository;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderFormatter;
use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class GetDashboardController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderEventRepository $events,
        private readonly OrderCheckoutSessionRepository $payments,
        private readonly UserRepository $users,
        private readonly ProductRepository $products,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $now = new \DateTimeImmutable();
        $todayStart = $now->setTime(0, 0, 0);
        $weekStart = $todayStart->modify('monday this week');
        $monthStart = $todayStart->modify('first day of this month');

        $recentOrders = array_map(
            static fn ($order): array => OrderFormatter::formatOrder($order),
            $this->orders->findRecentForAdmin(6),
        );

        $recentEvents = array_map(
            static fn (OrderEvent $event): array => [
                'id' => $event->getId(),
                'type' => $event->getType(),
                'message' => $event->getMessage(),
                'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
                'order' => [
                    'id' => $event->getOrder()->getId(),
                    'number' => $event->getOrder()->getNumber(),
                ],
                'actor' => [
                    'id' => $event->getActorUserId(),
                    'name' => $event->getActorName(),
                ],
            ],
            $this->events->findBy([], ['createdAt' => 'DESC'], 8),
        );

        return ApiResponse::success([
            'metrics' => [
                'today' => $this->orders->getSummaryBetween($todayStart, $now),
                'week' => $this->orders->getSummaryBetween($weekStart, $now),
                'month' => $this->orders->getSummaryBetween($monthStart, $now),
                'statusCounts' => $this->orders->getStatusCounts(),
                'issuesCount' => $this->orders->countWithOperationalIssues(),
                'lowStockCount' => $this->countGroupedLowStockProducts(3),
                'customersCount' => $this->users->count([]),
            ],
            'recentOrders' => $recentOrders,
            'recentEvents' => $recentEvents,
            'topCustomers' => $this->users->findAdminCustomerRows(null, 'highest_spent', 5),
            'payments' => [
                'statusCounts' => $this->payments->getStatusCounts(),
                'paidWithoutOrderCount' => $this->payments->countPaidWithoutOrder(),
                'recent' => array_map(
                    fn (OrderCheckoutSession $payment): array => $this->formatPayment($payment),
                    $this->payments->findRecentForDashboard(6),
                ),
                'attention' => array_map(
                    fn (OrderCheckoutSession $payment): array => $this->formatPayment($payment),
                    $this->payments->findAttentionItemsForDashboard(6),
                ),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayment(OrderCheckoutSession $payment): array
    {
        return [
            'id' => $payment->getId(),
            'status' => $payment->getStatus(),
            'statusLabel' => match ($payment->getStatus()) {
                OrderCheckoutSession::STATUS_OPEN => 'Ouvert',
                OrderCheckoutSession::STATUS_PAID => 'Payé',
                OrderCheckoutSession::STATUS_EXPIRED => 'Expiré',
                OrderCheckoutSession::STATUS_FAILED => 'Échoué',
                default => $payment->getStatus(),
            },
            'stripePaymentStatus' => $payment->getStripePaymentStatus(),
            'failureCode' => $payment->getFailureCode(),
            'failureMessage' => $payment->getFailureMessage(),
            'lastStripeEventType' => $payment->getLastStripeEventType(),
            'customerEmail' => $payment->getCustomerEmail(),
            'customerFullName' => $payment->getCustomerFullName(),
            'totalPriceCents' => $payment->getTotalPriceCents(),
            'currencyCode' => $payment->getCurrencyCode(),
            'orderId' => $payment->getOrderId(),
            'completedAt' => $payment->getCompletedAt()?->format(DATE_ATOM),
            'expiresAt' => $payment->getExpiresAt()?->format(DATE_ATOM),
            'createdAt' => $payment->getCreatedAt()->format(DATE_ATOM),
            'requiresAttention' => in_array($payment->getStatus(), [
                OrderCheckoutSession::STATUS_FAILED,
                OrderCheckoutSession::STATUS_EXPIRED,
            ], true) || ($payment->getStatus() === OrderCheckoutSession::STATUS_PAID && $payment->getOrderId() === null),
        ];
    }

    private function countGroupedLowStockProducts(int $threshold): int
    {
        $groups = [];

        /** @var Product $product */
        foreach ($this->products->findAllForAdmin() as $product) {
            if (!$product->isPublished()) {
                continue;
            }

            $key = $this->buildProductGroupKey($product);
            $groups[$key] = ($groups[$key] ?? 0) + $product->getStock();
        }

        return count(array_filter(
            $groups,
            static fn (int $totalStock): bool => $totalStock <= $threshold,
        ));
    }

    private function buildProductGroupKey(Product $product): string
    {
        $variantGroup = trim((string) ($product->getVariantGroup() ?? ''));
        if ($variantGroup !== '') {
            return $variantGroup;
        }

        $name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $product->getName()) ?? $product->getName();
        $name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name) ?? $name;
        $normalizedName = trim($name);

        return $normalizedName !== '' ? $normalizedName : $product->getSku();
    }
}
