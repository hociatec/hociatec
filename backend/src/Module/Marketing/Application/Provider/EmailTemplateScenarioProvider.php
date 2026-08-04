<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Provider;

final class EmailTemplateScenarioProvider
{
    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function getCampaignScenarioDefinitions(): array
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
                'defaults' => [
                    'registeredDays' => 30,
                ],
                'type' => 'campaign',
            ],
            'customers_with_orders' => [
                'label' => 'Clients ayant déjà commandé',
                'description' => 'Cible les utilisateurs avec au moins une commande.',
                'defaults' => [
                    'minimumOrders' => 1,
                ],
                'type' => 'campaign',
            ],
            'loyal_customers' => [
                'label' => 'Clients fidèles',
                'description' => 'Utilisateurs avec plusieurs commandes, idéal pour une offre VIP.',
                'defaults' => [
                    'minimumOrders' => 3,
                ],
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
                'defaults' => [
                    'recentDays' => 30,
                ],
                'type' => 'campaign',
            ],
            'high_value_customers' => [
                'label' => 'Clients à forte valeur',
                'description' => 'Clients dont le cumul de commandes dépasse un montant donné.',
                'defaults' => [
                    'minimumTotalCents' => 50000,
                ],
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
                'defaults' => [
                    'minimumPendingReviews' => 2,
                ],
                'type' => 'campaign',
            ],
            'inactive_customers' => [
                'label' => 'Clients inactifs',
                'description' => 'Clients avec commandes, mais plus aucune commande depuis X jours.',
                'defaults' => [
                    'inactiveDays' => 90,
                ],
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
                'defaults' => [
                    'registeredDays' => 30,
                ],
                'type' => 'campaign',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function getTransactionalTemplateScenarioDefinitions(): array
    {
        return [
            'order_created' => [
                'label' => 'Commande enregistrée / à régler',
                'description' => 'Email envoyé quand une commande est créée, notamment après conversion d’un devis en commande à régler.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'order_status_delivered' => [
                'label' => 'Commande livrée',
                'description' => 'Email envoyé lors du passage au statut livrée.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'order_invoice_issued' => [
                'label' => 'Facture disponible',
                'description' => 'Email envoyé quand une facture PDF/XML est générée et disponible.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'order_status_cancelled' => [
                'label' => 'Commande annulée',
                'description' => 'Email envoyé lors du passage au statut annulée.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'user_account_activation' => [
                'label' => 'Activation de compte',
                'description' => 'Email envoyé après inscription pour activer le compte client.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'password_reset' => [
                'label' => 'Réinitialisation de mot de passe',
                'description' => 'Email envoyé après demande de réinitialisation du mot de passe.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'quote_created' => [
                'label' => 'Devis créé',
                'description' => 'Email envoyé au client quand un devis est créé ou renvoyé.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'customer_voucher_offer' => [
                'label' => 'Bon de réduction client',
                'description' => 'Email envoyé depuis la fiche client pour transmettre un bon de réduction personnalisé.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'product_share' => [
                'label' => 'Partage de produit',
                'description' => 'Email envoyé quand une fiche produit est partagée par e-mail.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'contact_admin_notification' => [
                'label' => 'Notification contact interne',
                'description' => 'Email reçu par Hociatec après soumission du formulaire de contact.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'contact_acknowledgement' => [
                'label' => 'Accusé de réception contact',
                'description' => 'Email automatique envoyé au visiteur après le formulaire de contact.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'trade_in_created' => [
                'label' => 'Demande de reprise reçue',
                'description' => 'Email envoyé au client après l’envoi d’une demande de reprise.',
                'defaults' => [],
                'type' => 'transactional',
            ],
            'trade_in_status_changed' => [
                'label' => 'Suivi d’une reprise',
                'description' => 'Email envoyé au client lorsqu’une demande de reprise change de statut.',
                'defaults' => [],
                'type' => 'transactional',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function getTemplateScenarioDefinitions(): array
    {
        return $this->getCampaignScenarioDefinitions() + $this->getTransactionalTemplateScenarioDefinitions();
    }
}
