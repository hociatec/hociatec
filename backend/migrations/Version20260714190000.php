<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alimente le catalogue de services Hociatec avec des offres métier cohérentes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE quote_services
SET
    title = 'Création de site vitrine',
    description = 'Conception de site vitrine professionnel, responsive et administrable, avec accompagnement sur l’arborescence, les contenus, la mise en ligne et les bases SEO.',
    unit = 'prix fixe',
    duration_value = 10,
    duration_unit = 'day',
    price_cents = 120000,
    vat_rate_bps = 2000
WHERE id = 1
SQL);

        $services = [
            [
                'title' => 'Création de boutique e-commerce',
                'description' => 'Mise en place d’une boutique en ligne avec catalogue, fiches produits, tunnel de commande, moyens de paiement et accompagnement au lancement.',
                'unit' => 'prix fixe',
                'durationValue' => 15,
                'durationUnit' => 'day',
                'priceCents' => 240000,
            ],
            [
                'title' => 'Développement d’application web métier',
                'description' => 'Conception d’outil métier sur mesure pour digitaliser vos processus, centraliser vos données et fluidifier vos opérations internes.',
                'unit' => 'jour',
                'durationValue' => 5,
                'durationUnit' => 'day',
                'priceCents' => 55000,
            ],
            [
                'title' => 'Refonte de site internet',
                'description' => 'Modernisation d’un site existant avec amélioration du design, de la structure, des performances, de l’ergonomie et de la maintenabilité.',
                'unit' => 'prix fixe',
                'durationValue' => 8,
                'durationUnit' => 'day',
                'priceCents' => 150000,
            ],
            [
                'title' => 'Maintenance de site web',
                'description' => 'Suivi technique, mises à jour, correctifs, surveillance et petites évolutions pour garder votre site fiable, rapide et sécurisé.',
                'unit' => 'maintenance',
                'durationValue' => 1,
                'durationUnit' => 'day',
                'priceCents' => 8900,
            ],
            [
                'title' => 'Audit d’accessibilité numérique',
                'description' => 'Évaluation approfondie de l’accessibilité d’un site ou d’une application avec constats, priorisation des écarts et recommandations opérationnelles.',
                'unit' => 'audit',
                'durationValue' => 3,
                'durationUnit' => 'day',
                'priceCents' => 89000,
            ],
            [
                'title' => 'Audit technique web',
                'description' => 'Analyse de la structure technique, de la qualité du code, de l’architecture, des dépendances et des risques de maintenance.',
                'unit' => 'audit',
                'durationValue' => 2,
                'durationUnit' => 'day',
                'priceCents' => 69000,
            ],
            [
                'title' => 'Audit de performance web',
                'description' => 'Mesure et analyse des temps de chargement, des blocages front, des scripts tiers et des optimisations prioritaires à mettre en œuvre.',
                'unit' => 'audit',
                'durationValue' => 2,
                'durationUnit' => 'day',
                'priceCents' => 59000,
            ],
            [
                'title' => 'Audit SEO technique',
                'description' => 'Vérification de l’indexation, du balisage, du maillage, des performances et des freins techniques qui limitent votre visibilité naturelle.',
                'unit' => 'audit',
                'durationValue' => 2,
                'durationUnit' => 'day',
                'priceCents' => 59000,
            ],
            [
                'title' => 'Audit UX et parcours utilisateur',
                'description' => 'Étude des usages, de la compréhension de l’interface et des points de friction pour améliorer la conversion et le confort d’utilisation.',
                'unit' => 'audit',
                'durationValue' => 2,
                'durationUnit' => 'day',
                'priceCents' => 64000,
            ],
            [
                'title' => 'Diagnostic et dépannage informatique',
                'description' => 'Intervention de diagnostic matériel ou logiciel, recherche de panne, résolution d’incident et conseils de remise en état.',
                'unit' => 'intervention',
                'durationValue' => 2,
                'durationUnit' => 'hour',
                'priceCents' => 7900,
            ],
            [
                'title' => 'Assistance informatique à distance',
                'description' => 'Aide à distance pour la résolution de problèmes courants, la configuration d’outils et l’accompagnement des utilisateurs.',
                'unit' => 'heure',
                'durationValue' => 1,
                'durationUnit' => 'hour',
                'priceCents' => 4900,
            ],
            [
                'title' => 'Installation et configuration de postes',
                'description' => 'Préparation, installation et paramétrage de postes de travail, logiciels, comptes utilisateurs et périphériques.',
                'unit' => 'installation',
                'durationValue' => 3,
                'durationUnit' => 'hour',
                'priceCents' => 9900,
            ],
            [
                'title' => 'Installation réseau et NAS',
                'description' => 'Mise en place et configuration d’infrastructures réseau légères, NAS, sauvegardes locales et accès partagés sécurisés.',
                'unit' => 'installation',
                'durationValue' => 1,
                'durationUnit' => 'day',
                'priceCents' => 19900,
            ],
            [
                'title' => 'Formation bureautique et outils numériques',
                'description' => 'Formation pratique à l’usage des outils bureautiques, à l’organisation numérique et aux bonnes pratiques du quotidien.',
                'unit' => 'jour',
                'durationValue' => 1,
                'durationUnit' => 'day',
                'priceCents' => 32000,
            ],
            [
                'title' => 'Formation cybersécurité et bonnes pratiques',
                'description' => 'Sensibilisation aux mots de passe, au phishing, à la protection des données et aux réflexes essentiels de sécurité numérique.',
                'unit' => 'jour',
                'durationValue' => 1,
                'durationUnit' => 'day',
                'priceCents' => 36000,
            ],
            [
                'title' => 'Reconditionnement et remise en service de matériel',
                'description' => 'Contrôle, nettoyage, optimisation et remise en service de matériel informatique pour prolonger sa durée de vie utile.',
                'unit' => 'intervention',
                'durationValue' => 4,
                'durationUnit' => 'hour',
                'priceCents' => 12900,
            ],
        ];

        foreach ($services as $service) {
            $title = addslashes($service['title']);
            $description = addslashes($service['description']);
            $unit = addslashes($service['unit']);
            $durationValue = (int) $service['durationValue'];
            $durationUnit = addslashes($service['durationUnit']);
            $priceCents = (int) $service['priceCents'];

            $this->addSql(sprintf(
                "INSERT INTO quote_services (title, description, unit, duration_value, duration_unit, price_cents, vat_rate_bps, created_at, updated_at)
                 SELECT '%s', '%s', '%s', %d, '%s', %d, 2000, NOW(), NOW()
                 WHERE NOT EXISTS (SELECT 1 FROM quote_services WHERE title = '%s')",
                $title,
                $description,
                $unit,
                $durationValue,
                $durationUnit,
                $priceCents,
                $title
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quote_services WHERE title IN (
            'Création de boutique e-commerce',
            'Développement d’application web métier',
            'Refonte de site internet',
            'Maintenance de site web',
            'Audit d’accessibilité numérique',
            'Audit technique web',
            'Audit de performance web',
            'Audit SEO technique',
            'Audit UX et parcours utilisateur',
            'Diagnostic et dépannage informatique',
            'Assistance informatique à distance',
            'Installation et configuration de postes',
            'Installation réseau et NAS',
            'Formation bureautique et outils numériques',
            'Formation cybersécurité et bonnes pratiques',
            'Reconditionnement et remise en service de matériel'
        )");

        $this->addSql(<<<'SQL'
UPDATE quote_services
SET
    title = 'Création site web',
    description = 'Nous créons des sites sur mesures clef en main pour tout type de demande.',
    unit = NULL,
    duration_value = 48,
    duration_unit = 'hour',
    price_cents = 100000,
    vat_rate_bps = 2000
WHERE id = 1
SQL);
    }
}
