<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist rental periods on cart and order items, plus client rental requests';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('ALTER TABLE cart_items ADD rental_start_date DATE DEFAULT NULL');
        $this->addSql('DROP INDEX cart_item_unique_product_months ON cart_items');
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product_period ON cart_items (cart_id, product_id, rental_months, rental_start_date)');

        $this->addSql("ALTER TABLE order_items ADD selling_type VARCHAR(10) NOT NULL DEFAULT 'sale'");
        $this->addSql('ALTER TABLE order_items ADD rental_months INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_end_date DATE DEFAULT NULL');
        $this->addSql("ALTER TABLE order_items ADD rental_request_status VARCHAR(20) NOT NULL DEFAULT 'none'");
        $this->addSql('ALTER TABLE order_items ADD rental_request_type VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_requested_end_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_request_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE order_items ADD rental_request_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_order_items_rental_period ON order_items (selling_type, rental_start_date, rental_end_date)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('DROP INDEX cart_item_unique_product_period ON cart_items');
        $this->addSql('CREATE UNIQUE INDEX cart_item_unique_product_months ON cart_items (cart_id, product_id, rental_months)');
        $this->addSql('ALTER TABLE cart_items DROP rental_start_date');

        $this->addSql('DROP INDEX idx_order_items_rental_period ON order_items');
        $this->addSql('ALTER TABLE order_items DROP rental_request_updated_at');
        $this->addSql('ALTER TABLE order_items DROP rental_request_created_at');
        $this->addSql('ALTER TABLE order_items DROP rental_requested_end_date');
        $this->addSql('ALTER TABLE order_items DROP rental_request_type');
        $this->addSql('ALTER TABLE order_items DROP rental_request_status');
        $this->addSql('ALTER TABLE order_items DROP rental_end_date');
        $this->addSql('ALTER TABLE order_items DROP rental_start_date');
        $this->addSql('ALTER TABLE order_items DROP rental_months');
        $this->addSql('ALTER TABLE order_items DROP selling_type');
    }
}
