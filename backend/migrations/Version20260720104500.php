<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed missing transactional email templates managed from admin';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $templates = [
            [
                'name' => 'Activation de compte',
                'slug' => 'transaction-user-account-activation',
                'scenario_key' => 'user_account_activation',
                'subject_template' => 'Activez votre compte Hociatec',
                'html_body' => '<p>Bonjour {{first_name}},</p><p>Merci pour votre inscription. Pour activer votre compte, cliquez sur le lien ci-dessous, valide {{activation_expires_in}}.</p><p><a href="{{activation_url}}">Activer mon compte</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail.</p>',
                'text_body' => "Bonjour {{first_name}},\n\nMerci pour votre inscription. Pour activer votre compte, ouvrez le lien ci-dessous dans les {{activation_expires_in}} :\n{{activation_url}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.",
            ],
            [
                'name' => 'Réinitialisation de mot de passe',
                'slug' => 'transaction-password-reset',
                'scenario_key' => 'password_reset',
                'subject_template' => 'Réinitialisez votre mot de passe Hociatec',
                'html_body' => '<p>Bonjour {{first_name}},</p><p>Une demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.</p><p>Le lien ci-dessous vous permet de définir un nouveau mot de passe. Il reste valide pendant {{password_reset_expires_in}}.</p><p><a href="{{password_reset_url}}">Réinitialiser mon mot de passe</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.</p>',
                'text_body' => "Bonjour {{first_name}},\n\nUne demande de réinitialisation de mot de passe a été reçue pour votre compte Hociatec.\nPour définir un nouveau mot de passe, ouvrez ce lien dans l'heure :\n{{password_reset_url}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail.",
            ],
            [
                'name' => 'Devis créé',
                'slug' => 'transaction-quote-created',
                'scenario_key' => 'quote_created',
                'subject_template' => 'Votre devis {{quote_number}} a bien été créé',
                'html_body' => '<p>Bonjour {{customer_name}},</p><p>Votre devis a bien été créé par <strong>Hociatec</strong>.</p><p>Référence du devis : <strong>{{quote_number}}</strong>.</p><p>Montant total TTC : <strong>{{quote_total_eur}} EUR</strong>.</p><p>Date de validité : <strong>{{quote_valid_until}}</strong>.</p><p>Vous pouvez le consulter depuis votre espace client : <a href="{{quote_detail_url}}">{{quote_detail_url}}</a></p><p>Pensez à vérifier les éléments du devis et à revenir vers nous si vous souhaitez un ajustement.</p><p>Cordialement,<br>L’équipe Hociatec<br>{{mailer_from}}</p>',
                'text_body' => "Bonjour {{customer_name}},\n\nVotre devis a bien été créé par Hociatec.\nRéférence du devis : {{quote_number}}.\nMontant total TTC : {{quote_total_eur}} EUR.\nDate de validité : {{quote_valid_until}}.\n\nVous pouvez le consulter depuis votre espace client : {{quote_detail_url}}\n\nPensez à vérifier les éléments du devis et à revenir vers nous si vous souhaitez un ajustement.\n\nCordialement,\nL’équipe Hociatec\n{{mailer_from}}",
            ],
            [
                'name' => 'Partage de produit',
                'slug' => 'transaction-product-share',
                'scenario_key' => 'product_share',
                'subject_template' => 'Découvrir : {{product_name}}',
                'html_body' => '<p>Bonjour,</p><p>Voici un produit qui pourrait vous intéresser :</p><p><strong>{{product_name}}</strong></p><p>{{product_summary}}</p><p><strong>Prix :</strong> {{product_price_eur}} EUR</p><p><a href="{{product_url}}">Voir la fiche produit</a></p>',
                'text_body' => "Bonjour,\n\nVoici un produit qui pourrait vous intéresser :\n\n{{product_name}}\n{{product_summary}}\nPrix : {{product_price_eur}} EUR\nVoir la fiche produit : {{product_url}}",
            ],
            [
                'name' => 'Notification formulaire de contact',
                'slug' => 'transaction-contact-admin-notification',
                'scenario_key' => 'contact_admin_notification',
                'subject_template' => '[Contact] {{contact_subject}}',
                'html_body' => '<p><strong>Nom :</strong> {{contact_name}}</p><p><strong>E-mail :</strong> {{contact_email}}</p><p><strong>Sujet :</strong> {{contact_subject}}</p><p><strong>Message :</strong></p><p>{{contact_message}}</p>',
                'text_body' => "Nom : {{contact_name}}\nE-mail : {{contact_email}}\nSujet : {{contact_subject}}\n\n{{contact_message}}",
            ],
            [
                'name' => 'Accusé réception formulaire de contact',
                'slug' => 'transaction-contact-acknowledgement',
                'scenario_key' => 'contact_acknowledgement',
                'subject_template' => 'Merci de nous avoir contactés',
                'html_body' => '<p>Bonjour {{contact_name}},</p><p>Merci de nous avoir contactés. Nous avons bien reçu votre demande et allons la traiter rapidement.</p><p>Résumé de votre message :</p><blockquote style="border-left:4px solid #ddd;padding-left:8px;color:#444">{{contact_message}}</blockquote><p>Cet e-mail est automatique, merci de ne pas y répondre. Nous reviendrons vers vous dès que possible.</p>',
                'text_body' => "Bonjour {{contact_name}},\n\nMerci de nous avoir contactés. Nous avons bien reçu votre demande et allons la traiter rapidement.\n\nRésumé de votre message :\n{{contact_message}}\n\nCet e-mail est automatique, merci de ne pas y répondre.",
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
            'transaction-user-account-activation',
            'transaction-password-reset',
            'transaction-quote-created',
            'transaction-product-share',
            'transaction-contact-admin-notification',
            'transaction-contact-acknowledgement'
        )");
    }
}
