<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Service;

use App\Module\Rating\Application\Service\PendingReviewResolver;
use App\Module\User\Domain\Entity\User;

final readonly class PendingReviewNotificationProvider implements ComputedAccountNotificationProviderInterface
{
    public function __construct(
        private PendingReviewResolver $pendingReviews,
        private AccountNotificationFormatter $formatter,
    ) {
    }

    public function provide(User $user, \DateTimeImmutable $now): array
    {
        $pendingReviews = $this->pendingReviews->resolve($user);
        if ([] === $pendingReviews) {
            return [];
        }

        $orderItemIds = array_map(
            static fn (array $review): int => (int) ($review['orderItemId'] ?? 0),
            $pendingReviews,
        );
        sort($orderItemIds);
        $firstOrderId = (int) ($pendingReviews[0]['orderId'] ?? 0);
        $count = count($pendingReviews);

        return [
            $this->formatter->computedNotification(
                'reviews:'.implode(',', $orderItemIds),
                $count.' avis produit'.($count > 1 ? 's' : '').' à laisser',
                'Vous avez '.$count.' avis produit'.($count > 1 ? 's' : '').' à laisser sur une commande livrée.',
                $firstOrderId > 0 ? '/orders/'.$firstOrderId : '/orders/me',
                'pending_reviews',
                $now,
            ),
        ];
    }
}
