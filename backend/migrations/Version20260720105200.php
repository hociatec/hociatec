<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720105200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure order invoice issued transactional email template exists';
    }

    public function up(Schema $schema): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(id) FROM marketing_email_templates WHERE slug = ? OR scenario_key = ?',
            ['transaction-order-invoice-issued', 'order_invoice_issued'],
        );

        if ($exists > 0) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->insert('marketing_email_templates', [
            'name' => 'Facture disponible',
            'slug' => 'transaction-order-invoice-issued',
            'scenario_key' => 'order_invoice_issued',
            'subject_template' => 'Votre facture {{invoice_number}} est disponible',
            'html_body' => '<p>Bonjour {{first_name}},</p><p>Votre facture <strong>{{invoice_number}}</strong> du {{invoice_date}} est maintenant disponible pour la commande <strong>{{order_number}}</strong>.</p><p>Retrouvez-la depuis le détail de votre commande : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
            'text_body' => "Bonjour {{first_name}},\n\nVotre facture {{invoice_number}} du {{invoice_date}} est maintenant disponible pour la commande {{order_number}}.\n\nAccès commande : {{order_detail_url}}",
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM marketing_email_templates WHERE slug = 'transaction-order-invoice-issued'");
    }
}
