<?php

declare(strict_types=1);

namespace App\Module\Promotion\Application\Provider;

final readonly class PromotionAudienceProvider
{
    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>}>
     */
    public function definitions(): array
    {
        return [
            'all_users' => [
                'label' => 'Tout le monde',
                'description' => 'Promotion globale applicable à tous les visiteurs éligibles.',
                'defaults' => ['minimumCartTotalCents' => 0],
            ],
            'new_users' => [
                'label' => 'Nouveaux inscrits',
                'description' => 'Utilisateurs inscrits depuis moins de X jours.',
                'defaults' => ['registeredDays' => 30, 'minimumCartTotalCents' => 0],
            ],
            'first_order_users' => [
                'label' => 'Première commande',
                'description' => 'Utilisateurs n’ayant encore passé aucune commande.',
                'defaults' => ['minimumCartTotalCents' => 0],
            ],
            'returning_customers' => [
                'label' => 'Clients existants',
                'description' => 'Utilisateurs ayant déjà passé au moins une commande.',
                'defaults' => ['minimumCartTotalCents' => 0],
            ],
            'loyal_customers' => [
                'label' => 'Clients fidèles',
                'description' => 'Utilisateurs ayant atteint un nombre minimum de commandes.',
                'defaults' => ['minimumOrders' => 3, 'minimumCartTotalCents' => 0],
            ],
            'inactive_customers' => [
                'label' => 'Clients inactifs',
                'description' => 'Utilisateurs sans commande récente depuis X jours.',
                'defaults' => ['inactiveDays' => 90, 'minimumCartTotalCents' => 0],
            ],
        ];
    }
}
