<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée le module vouchers indépendant et détache les codes des promotions.';
    }

    public function up(Schema $schema): void
    {
        $tables = [];
        foreach ($schema->getTables() as $table) {
            $tables[$table->getName()] = $table;
        }

        if (!isset($tables['vouchers'])) {
            $this->addSql('CREATE TABLE vouchers (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(140) NOT NULL,
                code VARCHAR(64) NOT NULL,
                description VARCHAR(100) DEFAULT NULL,
                discount_type VARCHAR(20) NOT NULL,
                discount_value INT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                starts_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                ends_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_VOUCHERS_CODE (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (isset($tables['promotions'])) {
            $promotionColumns = [];
            foreach ($tables['promotions']->getColumns() as $column) {
                $promotionColumns[$column->getName()] = true;
            }

            if (isset($promotionColumns['coupon_code'])) {
                $this->addSql('INSERT INTO vouchers (name, code, description, discount_type, discount_value, is_active, starts_at, ends_at, created_at, updated_at)
                    SELECT name, coupon_code, description, discount_type, discount_value, is_active, starts_at, ends_at, created_at, updated_at
                    FROM promotions
                    WHERE coupon_code IS NOT NULL AND coupon_code <> ""');
                $this->addSql('DROP INDEX UNIQ_PROMOTIONS_COUPON_CODE ON promotions');
                $this->addSql('ALTER TABLE promotions DROP coupon_code');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $tables = [];
        foreach ($schema->getTables() as $table) {
            $tables[$table->getName()] = $table;
        }

        if (isset($tables['promotions']) && !$tables['promotions']->hasColumn('coupon_code')) {
            $this->addSql('ALTER TABLE promotions ADD coupon_code VARCHAR(64) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_PROMOTIONS_COUPON_CODE ON promotions (coupon_code)');
        }

        if (isset($tables['vouchers'])) {
            $this->addSql('DROP TABLE vouchers');
        }
    }
}
