<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assure l’unicité cart_id+product_id+rental_months et force rental_months à -1 pour les ventes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE cart_items SET rental_months = -1 WHERE rental_months IS NULL');

        $table = $schema->getTable('cart_items');
        if ($table->hasIndex('cart_item_unique_product')) {
            $this->addSql('DROP INDEX cart_item_unique_product ON cart_items');
        }

        if ($table->hasIndex('UNIQ_CART_ITEMS_CART_PRODUCT')) {
            $this->addSql('DROP INDEX UNIQ_CART_ITEMS_CART_PRODUCT ON cart_items');
        }

        $this->addSql('ALTER TABLE cart_items CHANGE rental_months rental_months INT NOT NULL DEFAULT -1');

        if (!$table->hasIndex('cart_item_unique_product_months')) {
            $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product_months ON cart_items (cart_id, product_id, rental_months)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('cart_items')->hasIndex('cart_item_unique_product_months')) {
            $this->addSql('DROP INDEX cart_item_unique_product_months ON cart_items');
        }

        $this->addSql('ALTER TABLE cart_items CHANGE rental_months rental_months INT DEFAULT NULL');
        $this->addSql('UPDATE cart_items SET rental_months = NULL WHERE rental_months = -1');
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product ON cart_items (cart_id, product_id)');
    }
}
