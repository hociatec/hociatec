<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815062049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refond le catalogue produit vers vente/location combinables et porte le mode sur les lignes panier.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX cart_item_unique_product_period ON cart_items');
        $this->addSql("ALTER TABLE cart_items ADD selling_type VARCHAR(10) NOT NULL DEFAULT 'sale'");
        $this->addSql("UPDATE cart_items SET selling_type = CASE WHEN rental_months > 0 THEN 'rental' ELSE 'sale' END");
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product_period ON cart_items (cart_id, product_id, selling_type, rental_months, rental_start_date)');
        $this->addSql('DROP INDEX idx_catalog_products_price_publication ON catalog_products');
        $this->addSql('ALTER TABLE catalog_products ADD sale_price_cents INT DEFAULT NULL, ADD rental_price_cents INT DEFAULT NULL, ADD available_for_sale TINYINT(1) DEFAULT 1 NOT NULL, ADD available_for_rental TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE catalog_products SET available_for_sale = CASE WHEN selling_type = 'sale' THEN 1 ELSE 0 END, available_for_rental = CASE WHEN selling_type = 'rental' THEN 1 ELSE 0 END, sale_price_cents = CASE WHEN selling_type = 'sale' THEN price_cents ELSE NULL END, rental_price_cents = CASE WHEN selling_type = 'rental' THEN price_cents ELSE NULL END");
        $this->addSql('ALTER TABLE catalog_products DROP price_cents, DROP selling_type');
        $this->addSql('CREATE INDEX idx_catalog_products_sale_price_publication ON catalog_products (is_published, sale_price_cents)');
        $this->addSql('CREATE INDEX idx_catalog_products_rental_price_publication ON catalog_products (is_published, rental_price_cents)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX cart_item_unique_product_period ON cart_items');
        $this->addSql("ALTER TABLE cart_items DROP selling_type");
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product_period ON cart_items (cart_id, product_id, rental_months, rental_start_date)');
        $this->addSql('DROP INDEX idx_catalog_products_sale_price_publication ON catalog_products');
        $this->addSql('DROP INDEX idx_catalog_products_rental_price_publication ON catalog_products');
        $this->addSql("ALTER TABLE catalog_products ADD price_cents INT NOT NULL DEFAULT 0, ADD selling_type VARCHAR(10) DEFAULT 'sale' NOT NULL");
        $this->addSql("UPDATE catalog_products SET selling_type = CASE WHEN available_for_rental = 1 AND available_for_sale = 0 THEN 'rental' ELSE 'sale' END, price_cents = CASE WHEN available_for_rental = 1 AND available_for_sale = 0 THEN COALESCE(rental_price_cents, 0) ELSE COALESCE(sale_price_cents, 0) END");
        $this->addSql('ALTER TABLE catalog_products DROP sale_price_cents, DROP rental_price_cents, DROP available_for_sale, DROP available_for_rental');
        $this->addSql('CREATE INDEX idx_catalog_products_price_publication ON catalog_products (is_published, price_cents)');
    }
}
