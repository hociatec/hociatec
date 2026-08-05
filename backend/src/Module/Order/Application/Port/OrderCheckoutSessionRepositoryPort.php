<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;

interface OrderCheckoutSessionRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?OrderCheckoutSession;

    public function findOneByStripeSessionId(string $stripeSessionId): ?OrderCheckoutSession;

    public function findOneByStripePaymentIntentId(string $stripePaymentIntentId): ?OrderCheckoutSession;

    public function findOneByToken(string $token): ?OrderCheckoutSession;

    public function findOneByOrderId(int $orderId): ?OrderCheckoutSession;

    public function findReusableOpenSessionForCart(User $user, string $cartToken): ?OrderCheckoutSession;

    public function findReusableOpenSessionForOrder(User $user, int $orderId): ?OrderCheckoutSession;

    /** @return array<string, int> */
    public function getStatusCounts(): array;

    public function countPaidWithoutOrder(): int;

    /** @return list<OrderCheckoutSession> */
    public function findRecentForDashboard(int $limit = 6): array;

    /** @return list<OrderCheckoutSession> */
    public function findAttentionItemsForDashboard(int $limit = 6): array;

    /** @return list<OrderCheckoutSession> */
    public function findRecentOpen(int $limit = 20): array;

    /** @return list<OrderCheckoutSession> */
    public function findRecentByOrderId(int $orderId, int $limit = 5): array;

    /** @return list<OrderCheckoutSession> */
    public function findForAdminList(?string $status, string $query): array;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<OrderCheckoutSession>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
}
