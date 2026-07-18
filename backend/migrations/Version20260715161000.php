<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update transactional email templates for order confirmation, delivery and cancellation';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $templates = [
            'transaction-order-created' => [
                'name' => 'Commande confirmée',
                'subject_template' => 'Commande {{order_number}} confirmée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> a bien été confirmée.</p>
<p>Montant total : <strong>{{order_total_eur}} EUR</strong>.</p>
<p>Vous pouvez suivre son évolution depuis votre espace client :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
<p>Merci pour votre confiance.</p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre commande {{order_number}} a bien été confirmée.

Montant total : {{order_total_eur}} EUR.

Suivi de commande :
{{order_detail_url}}

Merci pour votre confiance.
TEXT,
            ],
            'transaction-order-status-delivered' => [
                'name' => 'Commande livrée',
                'subject_template' => 'Commande {{order_number}} livrée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> est maintenant <strong>{{order_status_label}}</strong>.</p>
<p>Vous pouvez consulter le détail de la commande et télécharger votre facture ici :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre commande {{order_number}} est maintenant {{order_status_label}}.

Consulter la commande et télécharger la facture :
{{order_detail_url}}
TEXT,
            ],
            'transaction-order-status-cancelled' => [
                'name' => 'Commande annulée',
                'subject_template' => 'Commande {{order_number}} annulée',
                'html_body' => <<<'HTML'
<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> est désormais <strong>{{order_status_label}}</strong>.</p>
<p>Vous pouvez consulter son détail depuis votre espace client :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
<p>Si cette annulation vous semble incorrecte, contactez-nous.</p>
HTML,
                'text_body' => <<<'TEXT'
Bonjour {{first_name}},

Votre commande {{order_number}} est désormais {{order_status_label}}.

Détail de la commande :
{{order_detail_url}}

Si cette annulation vous semble incorrecte, contactez-nous.
TEXT,
            ],
        ];

        foreach ($templates as $slug => $template) {
            $this->addSql(
                'UPDATE marketing_email_templates
                 SET name = :name,
                     subject_template = :subject,
                     html_body = :html,
                     text_body = :text,
                     updated_at = :updatedAt
                 WHERE slug = :slug',
                [
                    'name' => $template['name'],
                    'subject' => $template['subject_template'],
                    'html' => $template['html_body'],
                    'text' => $template['text_body'],
                    'updatedAt' => $now,
                    'slug' => $slug,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
