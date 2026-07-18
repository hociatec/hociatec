<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed default transactional email templates for orders and invoices';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $templates = [
            [
                'name' => 'Commande créée',
                'slug' => 'transaction-order-created',
                'scenario_key' => 'order_created',
                'subject_template' => 'Commande {{order_number}} enregistrée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Nous confirmons la bonne prise en compte de votre commande <strong>{{order_number}}</strong> du {{order_created_at}}.</p>
<p>Montant total : <strong>{{order_total_eur}} EUR</strong>.</p>
<p>Vous pouvez suivre son évolution depuis votre espace client :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
<p>Merci pour votre confiance.</p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Nous confirmons la bonne prise en compte de votre commande {{order_number}} du {{order_created_at}}.

Montant total : {{order_total_eur}} EUR.

Suivi de commande :
{{order_detail_url}}

Merci pour votre confiance.
TEXT,
            ],
            [
                'name' => 'Facture générée',
                'slug' => 'transaction-order-invoice-issued',
                'scenario_key' => 'order_invoice_issued',
                'subject_template' => 'Votre facture {{invoice_number}} est disponible',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre facture <strong>{{invoice_number}}</strong> est maintenant disponible pour la commande <strong>{{order_number}}</strong>.</p>
<p>Date de facture : <strong>{{invoice_date}}</strong>.</p>
<p>Vous pouvez la télécharger depuis le détail de votre commande :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre facture {{invoice_number}} est maintenant disponible pour la commande {{order_number}}.
Date de facture : {{invoice_date}}.

Téléchargement depuis votre espace client :
{{order_detail_url}}
TEXT,
            ],
            [
                'name' => 'Commande confirmée',
                'slug' => 'transaction-order-status-confirmed',
                'scenario_key' => 'order_status_confirmed',
                'subject_template' => 'Commande {{order_number}} confirmée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> est désormais <strong>{{order_status_label}}</strong>.</p>
<p>Vous pouvez consulter son détail ici :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre commande {{order_number}} est désormais {{order_status_label}}.

Détail de la commande :
{{order_detail_url}}
TEXT,
            ],
            [
                'name' => 'Commande livrée',
                'slug' => 'transaction-order-status-delivered',
                'scenario_key' => 'order_status_delivered',
                'subject_template' => 'Commande {{order_number}} livrée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> est indiquée comme <strong>{{order_status_label}}</strong>.</p>
<p>Retrouvez son détail et votre facture depuis votre espace client :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre commande {{order_number}} est indiquée comme {{order_status_label}}.

Détail et facture :
{{order_detail_url}}
TEXT,
            ],
            [
                'name' => 'Commande annulée',
                'slug' => 'transaction-order-status-cancelled',
                'scenario_key' => 'order_status_cancelled',
                'subject_template' => 'Commande {{order_number}} annulée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> est désormais <strong>{{order_status_label}}</strong>.</p>
<p>Si cette situation vous semble anormale, nous vous invitons à nous contacter.</p>
<p>Détail de la commande :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre commande {{order_number}} est désormais {{order_status_label}}.

Si cette situation vous semble anormale, nous vous invitons à nous contacter.

Détail de la commande :
{{order_detail_url}}
TEXT,
            ],
        ];

        foreach ($templates as $template) {
            $exists = (int) $this->connection->fetchOne(
                'SELECT COUNT(id) FROM marketing_email_templates WHERE slug = ?',
                [$template['slug']],
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
            'transaction-order-created',
            'transaction-order-invoice-issued',
            'transaction-order-status-confirmed',
            'transaction-order-status-delivered',
            'transaction-order-status-cancelled'
        )");
    }
}
