<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add representative indexes for orders, checkout sessions, trade-in requests and catalog product listing filters';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('CREATE INDEX idx_orders_status_created ON orders (status, createdAt)');
        $this->addSql('CREATE INDEX idx_orders_user_created ON orders (user_id, createdAt)');
        $this->addSql('CREATE INDEX idx_orders_invoiced_at ON orders (invoiced_at)');

        $this->addSql('CREATE INDEX idx_checkout_user_cart_status ON order_checkout_sessions (user_id, cartToken, status)');
        $this->addSql('CREATE INDEX idx_checkout_user_order_status ON order_checkout_sessions (user_id, orderId, status)');
        $this->addSql('CREATE INDEX idx_checkout_status_created ON order_checkout_sessions (status, createdAt)');
        $this->addSql('CREATE INDEX idx_checkout_customer_email ON order_checkout_sessions (customerEmail)');

        $this->addSql('CREATE INDEX idx_trade_in_status_created ON trade_in_requests (status, createdAt)');
        $this->addSql('CREATE INDEX idx_trade_in_requester_created ON trade_in_requests (requester_user_id, createdAt)');
        $this->addSql('CREATE INDEX idx_trade_in_email ON trade_in_requests (email)');
        $this->addSql('CREATE INDEX idx_trade_in_closed_at ON trade_in_requests (closedAt)');

        $this->addSql('CREATE INDEX idx_catalog_products_publication ON catalog_products (isPublished, isFeaturedHome, createdAt)');
        $this->addSql('CREATE INDEX idx_catalog_products_category_publication ON catalog_products (category_id, isPublished, createdAt)');
        $this->addSql('CREATE INDEX idx_catalog_products_price_publication ON catalog_products (isPublished, priceCents)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('DROP INDEX idx_catalog_products_price_publication ON catalog_products');
        $this->addSql('DROP INDEX idx_catalog_products_category_publication ON catalog_products');
        $this->addSql('DROP INDEX idx_catalog_products_publication ON catalog_products');

        $this->addSql('DROP INDEX idx_trade_in_closed_at ON trade_in_requests');
        $this->addSql('DROP INDEX idx_trade_in_email ON trade_in_requests');
        $this->addSql('DROP INDEX idx_trade_in_requester_created ON trade_in_requests');
        $this->addSql('DROP INDEX idx_trade_in_status_created ON trade_in_requests');

        $this->addSql('DROP INDEX idx_checkout_customer_email ON order_checkout_sessions');
        $this->addSql('DROP INDEX idx_checkout_status_created ON order_checkout_sessions');
        $this->addSql('DROP INDEX idx_checkout_user_order_status ON order_checkout_sessions');
        $this->addSql('DROP INDEX idx_checkout_user_cart_status ON order_checkout_sessions');

        $this->addSql('DROP INDEX idx_orders_invoiced_at ON orders');
        $this->addSql('DROP INDEX idx_orders_user_created ON orders');
        $this->addSql('DROP INDEX idx_orders_status_created ON orders');
    }
}
