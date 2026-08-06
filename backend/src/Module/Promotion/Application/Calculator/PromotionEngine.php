<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Calculator;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Promotion\Application\Policy\PromotionEligibilityPolicy;
use App\Module\Promotion\Application\Port\PromotionRepositoryPort;
use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Promotion\Application\Provider\PromotionAudienceProvider;
use App\Module\User\Domain\Entity\User;

final class PromotionEngine
{
    public function __construct(
        private readonly PromotionRepositoryPort $promotions,
        private readonly PromotionFormatter $formatter,
        private readonly PromotionAudienceProvider $audiences,
        private readonly CartSubtotalCalculator $cartSubtotal,
        private readonly PromotionDiscountCalculator $discounts,
        private readonly PromotionEligibilityPolicy $eligibility,
    ) {
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>}>
     */
    public function getAudienceDefinitions(): array
    {
        return $this->audiences->definitions();
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
        return $this->calculateForSubtotal($this->cartSubtotal->calculate($cart), $user);
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
        $userStats = $user ? $this->loadUserStats($user) : null;

        foreach ($this->promotions->findActiveForDate($now) as $promotion) {
            if (!$this->eligibility->isEligible($promotion, $user, $subtotalPriceCents, $userStats, $now)) {
                continue;
            }

            $discountAmount = $this->discounts->compute($promotion, $subtotalPriceCents);
            if ($discountAmount <= 0) {
                continue;
            }

            $formatted = [
                ...$this->formatter->formatPromotion($promotion),
                'discountAmountCents' => $discountAmount,
            ];

            $eligiblePromotions[] = $formatted;

            if ($discountAmount > $bestAutomaticDiscount) {
                $bestAutomaticDiscount = $discountAmount;
                $bestAutomaticPromotion = $formatted;
            }
        }

        return [
            'subtotalPriceCents' => $subtotalPriceCents,
            'discountAmountCents' => $bestAutomaticDiscount,
            'totalPriceCents' => max(0, $subtotalPriceCents - $bestAutomaticDiscount),
            'appliedPromotion' => $bestAutomaticPromotion,
            'eligiblePromotions' => $eligiblePromotions,
        ];
    }

    /**
     * @return array{ordersCount:int,lastOrderAt:\DateTimeImmutable|null}
     */
    private function loadUserStats(User $user): array
    {
        return $this->promotions->findUserOrderStats($user);
    }
}
