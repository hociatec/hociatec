<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des templates marketing supplémentaires pour les nouveaux segments métier.';
    }

    public function up(Schema $schema): void
    {
        $this->insertTemplateIfMissing(
            'Relance deuxième commande',
            'relance-deuxieme-commande',
            'single_order_customers',
            'Votre prochaine solution Hociatec vous attend',
            '<p>Bonjour {{first_name}},</p><p>Merci pour votre première commande chez Hociatec. Nous espérons que votre expérience a été à la hauteur de vos attentes.</p><p>Nous pouvons déjà vous accompagner sur la suite avec une solution complémentaire adaptée à votre besoin actuel.</p><p>Notre équipe reste disponible pour vous orienter rapidement.</p><p>Bien cordialement,<br>Hociatec</p>',
            "Bonjour {{first_name}},\n\nMerci pour votre première commande chez Hociatec. Nous espérons que votre expérience a été à la hauteur de vos attentes.\n\nNous pouvons déjà vous accompagner sur la suite avec une solution complémentaire adaptée à votre besoin actuel.\n\nNotre équipe reste disponible pour vous orienter rapidement.\n\nBien cordialement,\nHociatec",
        );

        $this->insertTemplateIfMissing(
            'Conversion nouveaux comptes vérifiés',
            'conversion-nouveaux-comptes-verifies',
            'recent_verified_users',
            'Bienvenue chez Hociatec, on vous accompagne pour démarrer',
            '<p>Bonjour {{first_name}},</p><p>Votre compte Hociatec est bien activé. Vous pouvez dès maintenant découvrir nos produits, nos services et nos accompagnements.</p><p>Si vous souhaitez être guidé, nous pouvons vous aider à identifier rapidement la meilleure solution selon votre situation.</p><p>Connectez-vous à votre espace ou répondez directement à cet e-mail.</p><p>À très bientôt,<br>Hociatec</p>',
            "Bonjour {{first_name}},\n\nVotre compte Hociatec est bien activé. Vous pouvez dès maintenant découvrir nos produits, nos services et nos accompagnements.\n\nSi vous souhaitez être guidé, nous pouvons vous aider à identifier rapidement la meilleure solution selon votre situation.\n\nConnectez-vous à votre espace ou répondez directement à cet e-mail.\n\nÀ très bientôt,\nHociatec",
        );

        $this->insertTemplateIfMissing(
            'Relance comptes récents sans commande',
            'relance-comptes-recents-sans-commande',
            'verified_without_orders_recent',
            'Vous êtes inscrit, passons à l’étape suivante',
            '<p>Bonjour {{first_name}},</p><p>Votre compte Hociatec est actif, mais vous n’avez pas encore profité de nos solutions.</p><p>C’est souvent le bon moment pour faire un premier pas: découvrir nos offres, demander un conseil ou réserver un échange avec notre équipe.</p><p>Nous sommes disponibles pour vous orienter simplement.</p><p>Bien cordialement,<br>Hociatec</p>',
            "Bonjour {{first_name}},\n\nVotre compte Hociatec est actif, mais vous n'avez pas encore profité de nos solutions.\n\nC'est souvent le bon moment pour faire un premier pas: découvrir nos offres, demander un conseil ou réserver un échange avec notre équipe.\n\nNous sommes disponibles pour vous orienter simplement.\n\nBien cordialement,\nHociatec",
        );

        $this->insertTemplateIfMissing(
            'Offre premium clients haute valeur',
            'offre-premium-clients-haute-valeur',
            'high_value_customers',
            'Une attention réservée à nos clients privilégiés',
            '<p>Bonjour {{first_name}},</p><p>Merci pour votre confiance renouvelée envers Hociatec. Votre parcours avec nous représente déjà {{total_spent_eur}} EUR de commandes.</p><p>Nous souhaitions vous proposer un accompagnement plus personnalisé pour vos prochains besoins.</p><p>Si vous avez un projet en cours, notre équipe peut vous préparer une réponse prioritaire.</p><p>Avec nos remerciements,<br>Hociatec</p>',
            "Bonjour {{first_name}},\n\nMerci pour votre confiance renouvelée envers Hociatec. Votre parcours avec nous représente déjà {{total_spent_eur}} EUR de commandes.\n\nNous souhaitions vous proposer un accompagnement plus personnalisé pour vos prochains besoins.\n\nSi vous avez un projet en cours, notre équipe peut vous préparer une réponse prioritaire.\n\nAvec nos remerciements,\nHociatec",
        );

        $this->insertTemplateIfMissing(
            'Suivi clients récents',
            'suivi-clients-recents',
            'recent_customers',
            'Avez-vous besoin d’un accompagnement complémentaire ?',
            '<p>Bonjour {{first_name}},</p><p>Nous revenons vers vous suite à votre récente commande {{last_order_number}} du {{last_order_date}}.</p><p>Si vous avez besoin d’un complément, d’un conseil d’usage ou d’une solution associée, notre équipe peut vous aider rapidement.</p><p>Nous restons à votre disposition pour la suite.</p><p>Bien cordialement,<br>Hociatec</p>',
            "Bonjour {{first_name}},\n\nNous revenons vers vous suite à votre récente commande {{last_order_number}} du {{last_order_date}}.\n\nSi vous avez besoin d'un complément, d'un conseil d'usage ou d'une solution associée, notre équipe peut vous aider rapidement.\n\nNous restons à votre disposition pour la suite.\n\nBien cordialement,\nHociatec",
        );

        $this->insertTemplateIfMissing(
            'Relance avis multiple',
            'relance-avis-multiple',
            'customers_with_pending_reviews',
            'Il vous reste {{pending_reviews_count}} avis à partager',
            '<p>Bonjour {{first_name}},</p><p>Vous avez encore {{pending_reviews_count}} avis en attente suite à vos commandes chez Hociatec.</p><p>Quelques minutes suffisent pour partager votre retour. Vos avis nous aident à améliorer notre accompagnement et à mieux guider les futurs clients.</p><p>Merci pour votre temps et votre confiance.</p><p>À bientôt,<br>Hociatec</p>',
            "Bonjour {{first_name}},\n\nVous avez encore {{pending_reviews_count}} avis en attente suite à vos commandes chez Hociatec.\n\nQuelques minutes suffisent pour partager votre retour. Vos avis nous aident à améliorer notre accompagnement et à mieux guider les futurs clients.\n\nMerci pour votre temps et votre confiance.\n\nÀ bientôt,\nHociatec",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM marketing_email_templates WHERE slug IN (
            'relance-deuxieme-commande',
            'conversion-nouveaux-comptes-verifies',
            'relance-comptes-recents-sans-commande',
            'offre-premium-clients-haute-valeur',
            'suivi-clients-recents',
            'relance-avis-multiple'
        )");
    }

    private function insertTemplateIfMissing(
        string $name,
        string $slug,
        string $scenarioKey,
        string $subjectTemplate,
        string $htmlBody,
        string $textBody,
    ): void {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM marketing_email_templates WHERE slug = ?',
            [$slug],
        );

        if ($exists > 0) {
            return;
        }

        $this->addSql(
            'INSERT INTO marketing_email_templates (name, slug, scenario_key, subject_template, html_body, text_body, is_active, created_at, updated_at)
             VALUES (:name, :slug, :scenarioKey, :subjectTemplate, :htmlBody, :textBody, 1, NOW(), NOW())',
            [
                'name' => $name,
                'slug' => $slug,
                'scenarioKey' => $scenarioKey,
                'subjectTemplate' => $subjectTemplate,
                'htmlBody' => $htmlBody,
                'textBody' => $textBody,
            ],
        );
    }
}
