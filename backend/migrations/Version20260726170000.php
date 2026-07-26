<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor trade-in valuation data, remove legacy requests and seed trade-in email templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM trade_in_requests');
        $this->addSql('ALTER TABLE trade_in_requests ADD new_price_cents INT NOT NULL, ADD purchase_year SMALLINT NOT NULL');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $templates = [
            [
                'name' => 'Demande de reprise reçue',
                'slug' => 'transaction-trade-in-created',
                'scenario_key' => 'trade_in_created',
                'subject_template' => 'Votre demande de reprise {{trade_in_reference}} a bien été reçue',
                'html_body' => '<p>Bonjour {{customer_name}},</p><p>Nous avons bien reçu votre demande de reprise pour <strong>{{trade_in_product}}</strong>.</p><p>Référence : <strong>{{trade_in_reference}}</strong><br>Estimation indicative : <strong>{{trade_in_estimate}}</strong></p><p>Notre équipe va étudier votre demande et vous recontactera lors de la prochaine étape.</p><p>Vous pouvez suivre votre demande depuis votre espace client : <a href="{{trade_in_tracking_url}}">{{trade_in_tracking_url}}</a></p><p>Cordialement,<br>L’équipe Hociatec</p>',
                'text_body' => "Bonjour {{customer_name}},\n\nNous avons bien reçu votre demande de reprise pour {{trade_in_product}}.\nRéférence : {{trade_in_reference}}\nEstimation indicative : {{trade_in_estimate}}\n\nNotre équipe va étudier votre demande et vous recontactera lors de la prochaine étape.\n\nSuivi : {{trade_in_tracking_url}}\n\nCordialement,\nL’équipe Hociatec",
            ],
            [
                'name' => 'Suivi d’une demande de reprise',
                'slug' => 'transaction-trade-in-status',
                'scenario_key' => 'trade_in_status_changed',
                'subject_template' => 'Mise à jour de votre reprise {{trade_in_reference}} : {{trade_in_status}}',
                'html_body' => '<p>Bonjour {{customer_name}},</p><p>Le statut de votre demande de reprise <strong>{{trade_in_reference}}</strong> a évolué.</p><p>Matériel : <strong>{{trade_in_product}}</strong><br>Nouveau statut : <strong>{{trade_in_status}}</strong></p>{{trade_in_offer_block}}<p>Suivre la demande : <a href="{{trade_in_tracking_url}}">{{trade_in_tracking_url}}</a></p><p>Cordialement,<br>L’équipe Hociatec</p>',
                'text_body' => "Bonjour {{customer_name}},\n\nLe statut de votre demande {{trade_in_reference}} a évolué.\nMatériel : {{trade_in_product}}\nNouveau statut : {{trade_in_status}}\n\n{{trade_in_offer_text}}\nSuivre la demande : {{trade_in_tracking_url}}\n\nCordialement,\nL’équipe Hociatec",
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
        $this->addSql("DELETE FROM marketing_email_templates WHERE slug IN ('transaction-trade-in-created', 'transaction-trade-in-status')");
        $this->addSql('ALTER TABLE trade_in_requests DROP new_price_cents, DROP purchase_year');
    }
}
