<?php

declare(strict_types=1);

namespace App\Module\Promotion\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Order\Entity\Order;
use App\Module\Promotion\Entity\Promotion;
use App\Module\Promotion\Repository\PromotionRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class PromotionEngine
{
    public function __construct(
        private readonly PromotionRepository $promotions,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>}>
     */
    public function getAudienceDefinitions(): array
    {
        return [
            'all_users' => [
                'label' => 'Tout le monde',
                'description' => 'Promotion globale applicable à tous les visiteurs éligibles.',
                'defaults' => [
                    'minimumCartTotalCents' => 0,
                ],
            ],
            'new_users' => [
                'label' => 'Nouveaux inscrits',
                'description' => 'Utilisateurs inscrits depuis moins de X jours.',
                'defaults' => [
                    'registeredDays' => 30,
                    'minimumCartTotalCents' => 0,
                ],
            ],
            'first_order_users' => [
                'label' => 'Première commande',
                'description' => 'Utilisateurs n’ayant encore passé aucune commande.',
                'defaults' => [
                    'minimumCartTotalCents' => 0,
                ],
            ],
            'returning_customers' => [
                'label' => 'Clients existants',
                'description' => 'Utilisateurs ayant déjà passé au moins une commande.',
                'defaults' => [
                    'minimumCartTotalCents' => 0,
                ],
            ],
            'loyal_customers' => [
                'label' => 'Clients fidèles',
                'description' => 'Utilisateurs ayant atteint un nombre minimum de commandes.',
                'defaults' => [
                    'minimumOrders' => 3,
                    'minimumCartTotalCents' => 0,
                ],
            ],
            'inactive_customers' => [
                'label' => 'Clients inactifs',
                'description' => 'Utilisateurs sans commande récente depuis X jours.',
                'defaults' => [
                    'inactiveDays' => 90,
                    'minimumCartTotalCents' => 0,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *   subtotalPriceCents:int,
     *   discountAmountCents:int,
     *   totalPriceCents:int,
     *   appliedPromotion: array<string,mixed>|null,
     *   eligiblePromotions:list<array<string,mixed>>
     * }
     */
    public function calculateCartSummary(CartSession $cart, ?User $user): array
    {
        $subtotal = 0;

        /** @var CartItem $item */
        foreach ($cart->getItems() as $item) {
            $linePrice = $item->getProduct()->getPriceCents() * $item->getQuantity();
            if ($item->getProduct()->getSellingType() === 'rental') {
                $linePrice *= max(1, $item->getRentalMonths() ?? 1);
            }

            $subtotal += $linePrice;
        }

        return $this->calculateForSubtotal($subtotal, $user);
    }

    /**
     * @return array{
     *   subtotalPriceCents:int,
     *   discountAmountCents:int,
     *   totalPriceCents:int,
     *   appliedPromotion: array<string,mixed>|null,
     *   eligiblePromotions:list<array<string,mixed>>
     * }
     */
    public function calculateForSubtotal(int $subtotalPriceCents, ?User $user): array
    {
        $now = new \DateTimeImmutable();
        $eligiblePromotions = [];
        $bestAutomaticPromotion = null;
        $bestAutomaticDiscount = 0;
        $appliedPromotion = null;
        $appliedDiscount = 0;
        $userStats = $user ? $this->loadUserStats($user) : null;

        foreach ($this->promotions->findActiveForDate($now) as $promotion) {
            if (!$this->isPromotionEligible($promotion, $user, $subtotalPriceCents, $userStats, $now)) {
                continue;
            }

            $discountAmount = $this->computeDiscountAmount($promotion, $subtotalPriceCents);
            if ($discountAmount <= 0) {
                continue;
            }

            $formatted = [
                ...PromotionFormatter::formatPromotion($promotion),
                'discountAmountCents' => $discountAmount,
            ];

            $eligiblePromotions[] = $formatted;

            if ($discountAmount > $bestAutomaticDiscount) {
                $bestAutomaticDiscount = $discountAmount;
                $bestAutomaticPromotion = $formatted;
            }
        }

        if ($appliedPromotion === null) {
            $appliedPromotion = $bestAutomaticPromotion;
            $appliedDiscount = $bestAutomaticDiscount;
        }

        return [
            'subtotalPriceCents' => $subtotalPriceCents,
            'discountAmountCents' => $appliedDiscount,
            'totalPriceCents' => max(0, $subtotalPriceCents - $appliedDiscount),
            'appliedPromotion' => $appliedPromotion,
            'eligiblePromotions' => $eligiblePromotions,
        ];
    }

    private function computeDiscountAmount(Promotion $promotion, int $subtotalPriceCents): int
    {
        if ($subtotalPriceCents <= 0) {
            return 0;
        }

        if ($promotion->getDiscountType() === Promotion::TYPE_PERCENT) {
            $percent = max(0, min(100, $promotion->getDiscountValue()));

            return min($subtotalPriceCents, (int) round($subtotalPriceCents * ($percent / 100)));
        }

        return min($subtotalPriceCents, max(0, $promotion->getDiscountValue()));
    }

    /**
     * @param array{ordersCount:int,lastOrderAt:\DateTimeImmutable|null}|null $userStats
     */
    private function isPromotionEligible(
        Promotion $promotion,
        ?User $user,
        int $subtotalPriceCents,
        ?array $userStats,
        \DateTimeImmutable $now,
    ): bool {
        if (!$promotion->isActive()) {
            return false;
        }

        if ($promotion->getStartsAt() !== null && $promotion->getStartsAt() > $now) {
            return false;
        }

        if ($promotion->getEndsAt() !== null && $promotion->getEndsAt() < $now) {
            return false;
        }

        $criteria = $promotion->getCriteria();
        $minimumCartTotalCents = max(0, (int) ($criteria['minimumCartTotalCents'] ?? 0));
        if ($subtotalPriceCents < $minimumCartTotalCents) {
            return false;
        }

        return match ($promotion->getAudienceKey()) {
            'all_users' => true,
            'new_users' => $user !== null && $user->getCreatedAt() >= new \DateTimeImmutable(sprintf('-%d days', max(1, (int) ($criteria['registeredDays'] ?? 30)))),
            'first_order_users' => $user !== null && ($userStats['ordersCount'] ?? 0) === 0,
            'returning_customers' => $user !== null && ($userStats['ordersCount'] ?? 0) >= 1,
            'loyal_customers' => $user !== null && ($userStats['ordersCount'] ?? 0) >= max(2, (int) ($criteria['minimumOrders'] ?? 3)),
            'inactive_customers' => $user !== null
                && ($userStats['ordersCount'] ?? 0) >= 1
                && ($userStats['lastOrderAt'] instanceof \DateTimeImmutable)
                && $userStats['lastOrderAt'] < new \DateTimeImmutable(sprintf('-%d days', max(30, (int) ($criteria['inactiveDays'] ?? 90)))),
            default => false,
        };
    }

    /**
     * @return array{ordersCount:int,lastOrderAt:\DateTimeImmutable|null}
     */
    private function loadUserStats(User $user): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id) AS ordersCount', 'MAX(o.createdAt) AS lastOrderAt')
            ->from(Order::class, 'o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        $lastOrderAt = $result['lastOrderAt'] instanceof \DateTimeImmutable
            ? $result['lastOrderAt']
            : ($result['lastOrderAt'] instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($result['lastOrderAt']) : null);

        return [
            'ordersCount' => (int) ($result['ordersCount'] ?? 0),
            'lastOrderAt' => $lastOrderAt,
        ];
    }
}
