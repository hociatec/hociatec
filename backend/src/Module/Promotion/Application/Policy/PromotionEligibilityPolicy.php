<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Policy;

use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\User\Domain\Entity\User;

final readonly class PromotionEligibilityPolicy
{
    /**
     * @param array{ordersCount:int,lastOrderAt:\DateTimeImmutable|null}|null $userStats
     */
    public function isEligible(
        Promotion $promotion,
        ?User $user,
        int $subtotalPriceCents,
        ?array $userStats,
        \DateTimeImmutable $now,
    ): bool {
        if (!$this->isActiveForDate($promotion, $now)) {
            return false;
        }

        $criteria = $promotion->getCriteria();
        if ($subtotalPriceCents < max(0, (int) ($criteria['minimumCartTotalCents'] ?? 0))) {
            return false;
        }

        return match ($promotion->getAudienceKey()) {
            'all_users' => true,
            'new_users' => null !== $user && $user->getCreatedAt() >= new \DateTimeImmutable(sprintf('-%d days', max(1, (int) ($criteria['registeredDays'] ?? 30)))),
            'first_order_users' => null !== $user && ($userStats['ordersCount'] ?? 0) === 0,
            'returning_customers' => null !== $user && ($userStats['ordersCount'] ?? 0) >= 1,
            'loyal_customers' => null !== $user && ($userStats['ordersCount'] ?? 0) >= max(2, (int) ($criteria['minimumOrders'] ?? 3)),
            'inactive_customers' => $this->isInactiveCustomer($user, $userStats, $criteria),
            default => false,
        };
    }

    private function isActiveForDate(Promotion $promotion, \DateTimeImmutable $now): bool
    {
        return $promotion->isActive()
            && (null === $promotion->getStartsAt() || $promotion->getStartsAt() <= $now)
            && (null === $promotion->getEndsAt() || $promotion->getEndsAt() >= $now);
    }

    /**
     * @param array{ordersCount:int,lastOrderAt:\DateTimeImmutable|null}|null $userStats
     * @param array<string, mixed>                                            $criteria
     */
    private function isInactiveCustomer(?User $user, ?array $userStats, array $criteria): bool
    {
        return null !== $user
            && ($userStats['ordersCount'] ?? 0) >= 1
            && ($userStats['lastOrderAt'] instanceof \DateTimeImmutable)
            && $userStats['lastOrderAt'] < new \DateTimeImmutable(sprintf('-%d days', max(30, (int) ($criteria['inactiveDays'] ?? 90))));
    }
}
