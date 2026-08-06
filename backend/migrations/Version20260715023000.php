<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le module de promotions et le stockage des remises appliquées sur les commandes.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = array_flip($schemaManager->listTableNames());

        $promotionsCreated = !isset($tables['promotions']);

        if ($promotionsCreated) {
            $this->addSql('CREATE TABLE promotions (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(140) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                description VARCHAR(100) DEFAULT NULL,
                discount_type VARCHAR(20) NOT NULL,
                discount_value INT NOT NULL,
                audience_key VARCHAR(60) NOT NULL,
                criteria JSON NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                starts_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                ends_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_PROMOTIONS_SLUG (slug),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        $orderColumns = array_flip(array_keys($schemaManager->listTableColumns('orders')));

        if (!isset($orderColumns['subtotal_price_cents'])) {
            $this->addSql('ALTER TABLE orders ADD subtotal_price_cents INT NOT NULL DEFAULT 0');
        }

        if (!isset($orderColumns['discount_amount_cents'])) {
            $this->addSql('ALTER TABLE orders ADD discount_amount_cents INT NOT NULL DEFAULT 0');
        }

        if (!isset($orderColumns['applied_promotion_name'])) {
            $this->addSql('ALTER TABLE orders ADD applied_promotion_name VARCHAR(140) DEFAULT NULL');
        }

        if (!isset($orderColumns['applied_promotion_slug'])) {
            $this->addSql('ALTER TABLE orders ADD applied_promotion_slug VARCHAR(140) DEFAULT NULL');
        }

        $this->addSql('UPDATE orders SET subtotal_price_cents = total_price_cents WHERE subtotal_price_cents = 0');

        if ($promotionsCreated) {
            $this->addSql(<<<'SQL'
INSERT INTO promotions
    (name, slug, description, discount_type, discount_value, audience_key, criteria, is_active, starts_at, ends_at, created_at, updated_at)
VALUES
    (
        'Bienvenue nouveaux inscrits 10%',
        'bienvenue-nouveaux-inscrits-10',
        'Remise automatique pour les comptes récents',
        'percent',
        10,
        'new_users',
        '{"registeredDays":30,"minimumCartTotalCents":5000}',
        1,
        NULL,
        NULL,
        NOW(),
        NOW()
    ),
    (
        'Relance clients inactifs 20 EUR',
        'relance-clients-inactifs-20-eur',
        'Relance de réactivation sur panier minimum',
        'fixed_cents',
        2000,
        'inactive_customers',
        '{"inactiveDays":90,"minimumCartTotalCents":10000}',
        1,
        NULL,
        NULL,
        NOW(),
        NOW()
    )
SQL);
        } else {
            $seedCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM promotions');
            if (0 === $seedCount) {
                $this->addSql(<<<'SQL'
INSERT INTO promotions
    (name, slug, description, discount_type, discount_value, audience_key, criteria, is_active, starts_at, ends_at, created_at, updated_at)
VALUES
    (
        'Bienvenue nouveaux inscrits 10%',
        'bienvenue-nouveaux-inscrits-10',
        'Remise automatique pour les comptes récents',
        'percent',
        10,
        'new_users',
        '{"registeredDays":30,"minimumCartTotalCents":5000}',
        1,
        NULL,
        NULL,
        NOW(),
        NOW()
    ),
    (
        'Relance clients inactifs 20 EUR',
        'relance-clients-inactifs-20-eur',
        'Relance de réactivation sur panier minimum',
        'fixed_cents',
        2000,
        'inactive_customers',
        '{"inactiveDays":90,"minimumCartTotalCents":10000}',
        1,
        NULL,
        NULL,
        NOW(),
        NOW()
    )
SQL);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM promotions WHERE slug IN ('bienvenue-nouveaux-inscrits-10', 'relance-clients-inactifs-20-eur')");
        $this->addSql('DROP TABLE promotions');
        $this->addSql('ALTER TABLE orders DROP subtotal_price_cents, DROP discount_amount_cents, DROP applied_promotion_name, DROP applied_promotion_slug');
    }
}
