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

        $this->createIndexIfMissing('orders', 'idx_orders_status_created', 'CREATE INDEX idx_orders_status_created ON orders (status, created_at)');
        $this->createIndexIfMissing('orders', 'idx_orders_user_created', 'CREATE INDEX idx_orders_user_created ON orders (user_id, created_at)');
        $this->createIndexIfMissing('orders', 'idx_orders_invoiced_at', 'CREATE INDEX idx_orders_invoiced_at ON orders (invoiced_at)');

        $this->createIndexIfMissing('order_checkout_sessions', 'idx_checkout_user_cart_status', 'CREATE INDEX idx_checkout_user_cart_status ON order_checkout_sessions (user_id, cart_token, status)');
        $this->createIndexIfMissing('order_checkout_sessions', 'idx_checkout_user_order_status', 'CREATE INDEX idx_checkout_user_order_status ON order_checkout_sessions (user_id, order_id, status)');
        $this->createIndexIfMissing('order_checkout_sessions', 'idx_checkout_status_created', 'CREATE INDEX idx_checkout_status_created ON order_checkout_sessions (status, created_at)');
        $this->createIndexIfMissing('order_checkout_sessions', 'idx_checkout_customer_email', 'CREATE INDEX idx_checkout_customer_email ON order_checkout_sessions (customer_email)');

        $this->createIndexIfMissing('trade_in_requests', 'idx_trade_in_status_created', 'CREATE INDEX idx_trade_in_status_created ON trade_in_requests (status, created_at)');
        $this->createIndexIfMissing('trade_in_requests', 'idx_trade_in_requester_created', 'CREATE INDEX idx_trade_in_requester_created ON trade_in_requests (requester_user_id, created_at)');
        $this->createIndexIfMissing('trade_in_requests', 'idx_trade_in_email', 'CREATE INDEX idx_trade_in_email ON trade_in_requests (email)');
        $this->createIndexIfMissing('trade_in_requests', 'idx_trade_in_closed_at', 'CREATE INDEX idx_trade_in_closed_at ON trade_in_requests (closed_at)');

        $this->createIndexIfMissing('catalog_products', 'idx_catalog_products_publication', 'CREATE INDEX idx_catalog_products_publication ON catalog_products (is_published, is_featured_home, created_at)');
        $this->createIndexIfMissing('catalog_products', 'idx_catalog_products_category_publication', 'CREATE INDEX idx_catalog_products_category_publication ON catalog_products (category_id, is_published, created_at)');
        $this->createIndexIfMissing('catalog_products', 'idx_catalog_products_price_publication', 'CREATE INDEX idx_catalog_products_price_publication ON catalog_products (is_published, price_cents)');
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

    private function createIndexIfMissing(string $table, string $indexName, string $sql): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $indexName],
        );

        if (false === $row) {
            $this->addSql($sql);
        }
    }
}
