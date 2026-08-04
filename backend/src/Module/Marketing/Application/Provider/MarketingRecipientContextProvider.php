<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Provider;

use App\Module\Marketing\Application\Port\MarketingRecipientContextQuery;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;

final readonly class MarketingRecipientContextProvider
{
    public function __construct(
        private MarketingRecipientContextQuery $query,
        private string $frontendUrl,
    ) {
    }

    /** @return array<string, string> */
    public function provide(User $user): array
    {
        $orderStats = $this->query->orderStats($user);
        $lastOrder = $this->query->lastOrder($user);
        $pendingReviews = $this->query->pendingReviewsCount($user);
        $lastOrderAt = $orderStats['lastOrderAt'] ?? null;

        return [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'order_count' => (string) ((int) $orderStats['ordersCount']),
            'total_spent_eur' => number_format(((int) $orderStats['totalSpentCents']) / 100, 2, ',', ' '),
            'last_order_date' => $lastOrderAt instanceof \DateTimeInterface
                ? $lastOrderAt->format('d/m/Y')
                : '',
            'last_order_number' => $lastOrder instanceof Order ? $lastOrder->getNumber() : '',
            'days_since_last_order' => $lastOrderAt instanceof \DateTimeInterface
                ? (string) (new \DateTimeImmutable())->diff(\DateTimeImmutable::createFromInterface($lastOrderAt))->days
                : '',
            'pending_reviews_count' => (string) $pendingReviews,
            'app_frontend_url' => rtrim($this->frontendUrl, '/'),
        ];
    }
}
