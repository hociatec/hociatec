<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les codes promo générables et le stockage du code appliqué au panier.';
    }

    public function up(Schema $schema): void
    {
        $tables = [];
        foreach ($schema->getTables() as $table) {
            $tables[$table->getName()] = $table;
        }

        if (isset($tables['promotions'])) {
            $promotionColumns = [];
            foreach ($tables['promotions']->getColumns() as $column) {
                $promotionColumns[$column->getName()] = true;
            }

            if (!isset($promotionColumns['coupon_code'])) {
                $this->addSql('ALTER TABLE promotions ADD coupon_code VARCHAR(64) DEFAULT NULL');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_PROMOTIONS_COUPON_CODE ON promotions (coupon_code)');
            }
        }

        if (isset($tables['cart_sessions'])) {
            $cartColumns = [];
            foreach ($tables['cart_sessions']->getColumns() as $column) {
                $cartColumns[$column->getName()] = true;
            }

            if (!isset($cartColumns['promotion_code'])) {
                $this->addSql('ALTER TABLE cart_sessions ADD promotion_code VARCHAR(64) DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $tables = [];
        foreach ($schema->getTables() as $table) {
            $tables[$table->getName()] = $table;
        }

        if (isset($tables['promotions']) && $tables['promotions']->hasColumn('coupon_code')) {
            $this->addSql('DROP INDEX UNIQ_PROMOTIONS_COUPON_CODE ON promotions');
            $this->addSql('ALTER TABLE promotions DROP coupon_code');
        }

        if (isset($tables['cart_sessions']) && $tables['cart_sessions']->hasColumn('promotion_code')) {
            $this->addSql('ALTER TABLE cart_sessions DROP promotion_code');
        }
    }
}
