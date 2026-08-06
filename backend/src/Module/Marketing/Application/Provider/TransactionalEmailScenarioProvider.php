<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Provider;

final class TransactionalEmailScenarioProvider
{
    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function definitions(): array
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
}
