<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Provider;

final class CampaignEmailScenarioProvider
{
    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function definitions(): array
    {
        return [
            'all_verified_users' => [
                'label' => 'Tous les utilisateurs vérifiés',
                'description' => 'Audience large pour une annonce générale ou une nouveauté.',
                'defaults' => [],
                'type' => 'campaign',
            ],
            'recent_verified_users' => [
                'label' => 'Nouveaux comptes vérifiés',
                'description' => 'Utilisateurs vérifiés inscrits récemment, utiles pour une première conversion.',
                'defaults' => ['registeredDays' => 30],
                'type' => 'campaign',
            ],
            'customers_with_orders' => [
                'label' => 'Clients ayant déjà commandé',
                'description' => 'Cible les utilisateurs avec au moins une commande.',
                'defaults' => ['minimumOrders' => 1],
                'type' => 'campaign',
            ],
            'loyal_customers' => [
                'label' => 'Clients fidèles',
                'description' => 'Utilisateurs avec plusieurs commandes, idéal pour une offre VIP.',
                'defaults' => ['minimumOrders' => 3],
                'type' => 'campaign',
            ],
            'single_order_customers' => [
                'label' => 'Clients avec une seule commande',
                'description' => 'Parfait pour déclencher une deuxième commande avec une relance ciblée.',
                'defaults' => [],
                'type' => 'campaign',
            ],
            'recent_customers' => [
                'label' => 'Clients récents',
                'description' => 'Clients ayant commandé dans une fenêtre récente, pour cross-sell ou accompagnement.',
                'defaults' => ['recentDays' => 30],
                'type' => 'campaign',
            ],
            'high_value_customers' => [
                'label' => 'Clients à forte valeur',
                'description' => 'Clients dont le cumul de commandes dépasse un montant donné.',
                'defaults' => ['minimumTotalCents' => 50000],
                'type' => 'campaign',
            ],
            'customers_without_review' => [
                'label' => 'Clients sans avis',
                'description' => 'Utilisateurs ayant au moins un article commandé sans avis publié.',
                'defaults' => [],
                'type' => 'campaign',
            ],
            'customers_with_pending_reviews' => [
                'label' => 'Clients avec plusieurs avis en attente',
                'description' => 'Clients ayant plusieurs produits commandés encore sans avis.',
                'defaults' => ['minimumPendingReviews' => 2],
                'type' => 'campaign',
            ],
            'inactive_customers' => [
                'label' => 'Clients inactifs',
                'description' => 'Clients avec commandes, mais plus aucune commande depuis X jours.',
                'defaults' => ['inactiveDays' => 90],
                'type' => 'campaign',
            ],
            'verified_without_orders' => [
                'label' => 'Comptes vérifiés sans commande',
                'description' => 'Nouveaux comptes ou prospects à convertir.',
                'defaults' => [],
                'type' => 'campaign',
            ],
            'verified_without_orders_recent' => [
                'label' => 'Comptes récents sans commande',
                'description' => 'Comptes vérifiés créés récemment mais encore sans commande.',
                'defaults' => ['registeredDays' => 30],
                'type' => 'campaign',
            ],
        ];
    }
}
