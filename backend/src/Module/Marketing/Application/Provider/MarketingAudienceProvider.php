<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Provider;

use App\Module\Marketing\Application\Port\MarketingAudienceQuery;
use App\Module\User\Domain\Entity\User;

final readonly class MarketingAudienceProvider
{
    public function __construct(
        private MarketingAudienceQuery $audiences,
        private EmailTemplateScenarioProvider $scenarioProvider,
    ) {
    }

    /** @return array<string, array<string, mixed>> */
    public function getSegmentDefinitions(): array
    {
        return $this->scenarioProvider->getCampaignScenarioDefinitions();
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     */
    public function preview(string $segmentKey, array $criteria): array
    {
        return [
            'count' => count($this->resolveRecipients($segmentKey, $criteria)),
            'recipients' => array_map(
                static fn (User $user): array => [
                    'id' => (int) $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                ],
                $this->resolveRecipients($segmentKey, $criteria, 10),
            ),
            'description' => $this->describe($segmentKey, $criteria),
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<User>
     */
    public function resolveRecipients(string $segmentKey, array $criteria, ?int $limit = null): array
    {
        return $this->audiences->resolveRecipients($segmentKey, $criteria, $limit);
    }

    /** @param array<string, mixed> $criteria */
    private function describe(string $segmentKey, array $criteria): string
    {
        return match ($segmentKey) {
            'all_verified_users' => 'Tous les comptes vérifiés.',
            'recent_verified_users' => sprintf('Comptes vérifiés créés depuis moins de %d jours.', max(7, (int) ($criteria['registeredDays'] ?? 30))),
            'customers_with_orders' => sprintf('Clients avec au moins %d commande(s).', max(1, (int) ($criteria['minimumOrders'] ?? 1))),
            'loyal_customers' => sprintf('Clients avec au moins %d commandes.', max(2, (int) ($criteria['minimumOrders'] ?? 3))),
            'single_order_customers' => 'Clients ayant exactement une commande.',
            'recent_customers' => sprintf('Clients ayant commandé au cours des %d derniers jours.', max(7, (int) ($criteria['recentDays'] ?? 30))),
            'high_value_customers' => sprintf('Clients avec au moins %.2f EUR de commandes cumulées.', max(1000, (int) ($criteria['minimumTotalCents'] ?? 50000)) / 100),
            'customers_without_review' => 'Clients ayant commandé mais sans avis publié sur au moins un article.',
            'customers_with_pending_reviews' => sprintf('Clients avec au moins %d avis en attente.', max(1, (int) ($criteria['minimumPendingReviews'] ?? 2))),
            'inactive_customers' => sprintf('Clients inactifs depuis plus de %d jours.', max(30, (int) ($criteria['inactiveDays'] ?? 90))),
            'verified_without_orders' => 'Comptes vérifiés sans aucune commande.',
            'verified_without_orders_recent' => sprintf('Comptes vérifiés créés depuis moins de %d jours et sans commande.', max(7, (int) ($criteria['registeredDays'] ?? 30))),
            default => 'Audience marketing.',
        };
    }
}
