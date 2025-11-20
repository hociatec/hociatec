<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise la durée de location des articles du panier et élargit la contrainte d\'unicité aux durées.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE cart_items SET rental_months = -1 WHERE rental_months IS NULL');
        $this->addSql('ALTER TABLE cart_items DROP INDEX UNIQ_CART_ITEMS_CART_PRODUCT');
        $this->addSql('ALTER TABLE cart_items CHANGE rental_months rental_months INT NOT NULL DEFAULT -1');
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product_months ON cart_items (cart_id, product_id, rental_months)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX cart_item_unique_product_months ON cart_items');
        $this->addSql('ALTER TABLE cart_items CHANGE rental_months rental_months INT DEFAULT NULL');
        $this->addSql('UPDATE cart_items SET rental_months = NULL WHERE rental_months = -1');
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product ON cart_items (cart_id, product_id)');
    }
}
