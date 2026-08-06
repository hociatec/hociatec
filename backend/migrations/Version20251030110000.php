<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders and order_items tables';
    }

    public function up(Schema $schema): void
    {
        // orders table
        $this->addSql("CREATE TABLE orders (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            number VARCHAR(30) NOT NULL,
            status VARCHAR(20) NOT NULL,
            total_price_cents INT NOT NULL,
            shipping_name VARCHAR(180) DEFAULT NULL,
            shipping_address LONGTEXT DEFAULT NULL,
            shipping_postal_code VARCHAR(20) DEFAULT NULL,
            shipping_city VARCHAR(100) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_ORDERS_USER (user_id),
            UNIQUE INDEX UNIQ_ORDERS_NUMBER (number),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_ORDERS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');

        // order_items table
        $this->addSql('CREATE TABLE order_items (
            id INT AUTO_INCREMENT NOT NULL,
            order_id INT NOT NULL,
            product_id INT DEFAULT NULL,
            product_name VARCHAR(180) NOT NULL,
            product_sku VARCHAR(60) NOT NULL,
            unit_price_cents INT NOT NULL,
            quantity INT NOT NULL,
            INDEX IDX_ORDER_ITEMS_ORDER (order_id),
            INDEX IDX_ORDER_ITEMS_PRODUCT (product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_ORDER_ITEMS_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_ORDER_ITEMS_PRODUCT FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_ORDER_ITEMS_ORDER');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_ORDER_ITEMS_PRODUCT');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_ORDERS_USER');
        $this->addSql('DROP TABLE orders');
    }
}
