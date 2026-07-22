<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720105800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure every declared email scenario has a default template';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $templates = [
            [
                'name' => 'Annonce générale',
                'slug' => 'campaign-all-verified-users',
                'scenario_key' => 'all_verified_users',
                'subject_template' => 'Une nouveauté Hociatec à découvrir',
                'html_body' => '<p>Bonjour {{first_name}},</p><p>Nous avons une nouveauté à vous partager.</p><p>Découvrez nos offres et services depuis votre espace Hociatec :</p><p><a href="{{app_frontend_url}}">{{app_frontend_url}}</a></p><p>À bientôt,<br>L’équipe Hociatec</p>',
                'text_body' => "Bonjour {{first_name}},\n\nNous avons une nouveauté à vous partager.\n\nDécouvrez nos offres et services depuis votre espace Hociatec :\n{{app_frontend_url}}\n\nÀ bientôt,\nL’équipe Hociatec",
            ],
            [
                'name' => 'Relance clients ayant commandé',
                'slug' => 'campaign-customers-with-orders',
                'scenario_key' => 'customers_with_orders',
                'subject_template' => 'Merci pour votre confiance, {{first_name}}',
                'html_body' => '<p>Bonjour {{first_name}},</p><p>Merci pour votre confiance. Vous avez déjà passé {{order_count}} commande(s) chez Hociatec.</p><p>Nous restons disponibles pour vos prochains besoins numériques, achats, locations ou interventions.</p><p><a href="{{app_frontend_url}}">Retourner sur Hociatec</a></p>',
                'text_body' => "Bonjour {{first_name}},\n\nMerci pour votre confiance. Vous avez déjà passé {{order_count}} commande(s) chez Hociatec.\n\nNous restons disponibles pour vos prochains besoins numériques, achats, locations ou interventions.\n\n{{app_frontend_url}}",
            ],
            [
                'name' => 'Bon de réduction client',
                'slug' => 'transaction-customer-voucher-offer',
                'scenario_key' => 'customer_voucher_offer',
                'subject_template' => 'Votre bon de réduction {{voucher_code}}',
                'html_body' => '<p>Bonjour {{first_name}},</p><p>Voici votre bon de réduction <strong>{{voucher_code}}</strong>.</p><p>Valeur : <strong>{{voucher_value_label}}</strong>.</p><p>{{voucher_description}}</p><p>Utilisez-le sur votre prochaine commande depuis <a href="{{cart_url}}">{{cart_url}}</a>.</p>',
                'text_body' => "Bonjour {{first_name}},\n\nVoici votre bon de réduction {{voucher_code}}.\nValeur : {{voucher_value_label}}.\n{{voucher_description}}\n\nUtilisez-le sur votre prochaine commande : {{cart_url}}",
            ],
        ];

        foreach ($templates as $template) {
            $exists = (int) $this->connection->fetchOne(
                'SELECT COUNT(id) FROM marketing_email_templates WHERE slug = ? OR scenario_key = ?',
                [$template['slug'], $template['scenario_key']],
            );

            if ($exists > 0) {
                continue;
            }

            $this->connection->insert('marketing_email_templates', [
                'name' => $template['name'],
                'slug' => $template['slug'],
                'scenario_key' => $template['scenario_key'],
                'subject_template' => $template['subject_template'],
                'html_body' => $template['html_body'],
                'text_body' => $template['text_body'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM marketing_email_templates WHERE slug IN (
            'campaign-all-verified-users',
            'campaign-customers-with-orders',
            'transaction-customer-voucher-offer'
        )");
    }
}
