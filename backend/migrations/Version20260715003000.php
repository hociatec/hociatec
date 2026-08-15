<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les templates email marketing et l’historique des campagnes admin.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = array_flip($schemaManager->listTableNames());
        $templatesTableCreated = false;

        if (!isset($tables['marketing_email_templates'])) {
            $templatesTableCreated = true;
            $this->addSql('CREATE TABLE marketing_email_templates (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                scenario_key VARCHAR(50) NOT NULL,
                subject_template VARCHAR(180) NOT NULL,
                html_body LONGTEXT NOT NULL,
                text_body LONGTEXT DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_MARKETING_EMAIL_TEMPLATES_SLUG (slug),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!isset($tables['marketing_email_campaigns'])) {
            $this->addSql('CREATE TABLE marketing_email_campaigns (
                id INT AUTO_INCREMENT NOT NULL,
                template_id INT DEFAULT NULL,
                name VARCHAR(140) NOT NULL,
                segment_key VARCHAR(60) NOT NULL,
                criteria JSON NOT NULL,
                subject_snapshot VARCHAR(180) NOT NULL,
                html_snapshot LONGTEXT NOT NULL,
                text_snapshot LONGTEXT DEFAULT NULL,
                recipients_count INT NOT NULL,
                created_by_email VARCHAR(180) DEFAULT NULL,
                sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_MARKETING_EMAIL_CAMPAIGNS_TEMPLATE (template_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

            $this->addSql('ALTER TABLE marketing_email_campaigns ADD CONSTRAINT FK_MARKETING_EMAIL_CAMPAIGNS_TEMPLATE FOREIGN KEY (template_id) REFERENCES marketing_email_templates (id) ON DELETE SET NULL');
        }

        $templatesCount = $templatesTableCreated
            ? 0
            : (int) $this->connection->fetchOne('SELECT COUNT(*) FROM marketing_email_templates');
        if (0 === $templatesCount) {
            $this->addSql(<<<'SQL'
INSERT INTO marketing_email_templates
    (name, slug, scenario_key, subject_template, html_body, text_body, is_active, created_at, updated_at)
VALUES
    (
        'Relance avis après commande',
        'relance-avis-apres-commande',
        'customers_without_review',
        'Votre avis compte pour Hociatec',
        '<p>Bonjour {{first_name}},</p><p>Merci pour votre confiance. Vous avez récemment commandé chez Hociatec et nous serions ravis de connaître votre retour.</p><p>Il vous reste {{pending_reviews_count}} avis à déposer. Votre expérience nous aide à améliorer notre service et à guider les futurs clients.</p><p>Connectez-vous à votre espace client pour partager votre avis.</p><p>À bientôt,<br>Hociatec</p>',
        "Bonjour {{first_name}},\n\nMerci pour votre confiance. Vous avez récemment commandé chez Hociatec et nous serions ravis de connaître votre retour.\n\nIl vous reste {{pending_reviews_count}} avis à déposer. Votre expérience nous aide à améliorer notre service et à guider les futurs clients.\n\nConnectez-vous à votre espace client pour partager votre avis.\n\nÀ bientôt,\nHociatec",
        1,
        NOW(),
        NOW()
    ),
    (
        'Réactivation clients inactifs',
        'reactivation-clients-inactifs',
        'inactive_customers',
        'Nous avons préparé quelque chose pour votre retour',
        '<p>Bonjour {{first_name}},</p><p>Nous n''avons pas eu le plaisir de vous revoir récemment depuis votre dernière commande {{last_order_number}} du {{last_order_date}}.</p><p>Nous avons peut-être de nouvelles solutions ou offres adaptées à vos besoins actuels. C''est le bon moment pour reprendre contact avec Hociatec.</p><p>Répondez simplement à cet e-mail ou reconnectez-vous à votre espace client pour découvrir les nouveautés.</p><p>Bien cordialement,<br>Hociatec</p>',
        "Bonjour {{first_name}},\n\nNous n'avons pas eu le plaisir de vous revoir récemment depuis votre dernière commande {{last_order_number}} du {{last_order_date}}.\n\nNous avons peut-être de nouvelles solutions ou offres adaptées à vos besoins actuels. C'est le bon moment pour reprendre contact avec Hociatec.\n\nRépondez simplement à cet e-mail ou reconnectez-vous à votre espace client pour découvrir les nouveautés.\n\nBien cordialement,\nHociatec",
        1,
        NOW(),
        NOW()
    ),
    (
        'Offre fidélité clients récurrents',
        'offre-fidelite-clients-recurrents',
        'loyal_customers',
        'Merci pour votre fidélité chez Hociatec',
        '<p>Bonjour {{first_name}},</p><p>Vous faites partie de nos clients les plus fidèles avec déjà {{order_count}} commande(s) chez Hociatec. Merci pour votre confiance renouvelée.</p><p>Nous souhaitions vous réserver une attention particulière et vous proposer une offre adaptée à votre profil.</p><p>Si vous avez un nouveau besoin, un projet ou une question, notre équipe est à votre disposition.</p><p>Avec nos remerciements,<br>Hociatec</p>',
        "Bonjour {{first_name}},\n\nVous faites partie de nos clients les plus fidèles avec déjà {{order_count}} commande(s) chez Hociatec. Merci pour votre confiance renouvelée.\n\nNous souhaitions vous réserver une attention particulière et vous proposer une offre adaptée à votre profil.\n\nSi vous avez un nouveau besoin, un projet ou une question, notre équipe est à votre disposition.\n\nAvec nos remerciements,\nHociatec",
        1,
        NOW(),
        NOW()
    ),
    (
        'Bienvenue sans commande',
        'bienvenue-sans-commande',
        'verified_without_orders',
        'Bienvenue chez Hociatec',
        '<p>Bonjour {{first_name}},</p><p>Votre compte Hociatec est désormais actif. Nous sommes ravis de vous accueillir.</p><p>Si vous n''avez pas encore passé commande, c''est peut-être le bon moment pour découvrir nos produits, services et accompagnements.</p><p>Notre équipe peut également vous aider à identifier la meilleure solution selon votre besoin.</p><p>À très bientôt,<br>Hociatec</p>',
        "Bonjour {{first_name}},\n\nVotre compte Hociatec est désormais actif. Nous sommes ravis de vous accueillir.\n\nSi vous n'avez pas encore passé commande, c'est peut-être le bon moment pour découvrir nos produits, services et accompagnements.\n\nNotre équipe peut également vous aider à identifier la meilleure solution selon votre besoin.\n\nÀ très bientôt,\nHociatec",
        1,
        NOW(),
        NOW()
    )
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketing_email_campaigns DROP FOREIGN KEY FK_MARKETING_EMAIL_CAMPAIGNS_TEMPLATE');
        $this->addSql('DROP TABLE marketing_email_campaigns');
        $this->addSql('DROP TABLE marketing_email_templates');
    }
}
