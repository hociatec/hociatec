<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update order created transactional email wording for pending payment and quote-origin orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
UPDATE marketing_email_templates
SET name = 'Commande enregistrée / à régler',
    subject_template = 'Commande {{order_number}} {{order_email_status_title}}',
    html_body = '<p>Bonjour {{first_name}},</p>
<p>Votre commande <strong>{{order_number}}</strong> a bien été enregistrée pour un montant total de <strong>{{order_total_eur}} EUR</strong>.</p>
<p>{{order_origin_sentence}}</p>
<p>{{order_payment_instruction}}</p>
<p>{{order_payment_next_step}}</p>
<p>Vous pouvez accéder au détail de la commande depuis votre espace client :</p>
<p><a href="{{order_detail_url}}">{{order_detail_url}}</a></p>
<p>Tant que le règlement n’est pas finalisé, la commande reste en attente et la facture n’est pas disponible au téléchargement.</p>
<p>Merci pour votre confiance.</p>',
    text_body = 'Bonjour {{first_name}},

Votre commande {{order_number}} a bien été enregistrée pour un montant total de {{order_total_eur}} EUR.

{{order_origin_sentence}}

{{order_payment_instruction}}

{{order_payment_next_step}}

Vous pouvez accéder au détail de la commande depuis votre espace client :
{{order_detail_url}}

Tant que le règlement n’est pas finalisé, la commande reste en attente et la facture n’est pas disponible au téléchargement.

Merci pour votre confiance.',
    updated_at = CURRENT_TIMESTAMP
WHERE slug = 'transaction-order-created'
SQL
        );
    }

    public function down(Schema $schema): void
    {
    }
}
