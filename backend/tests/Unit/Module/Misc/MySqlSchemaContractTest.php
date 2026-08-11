<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;

final class MySqlSchemaContractTest extends \PHPUnit\Framework\TestCase
{
    public function testDoctrineMetadataGeneratesExpectedMySqlConstraintsAndIndexes(): void
    {
        $sql = implode("\n", $this->mysqlSchemaSql());

        foreach ([
            'ENGINE = InnoDB',
            'CREATE TABLE orders',
            'UNIQUE INDEX UNIQ_E52FFDEE96901F54 (number)',
            'INDEX idx_orders_status_created (status, created_at)',
            'INDEX idx_orders_user_created (user_id, created_at)',
            'ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE',
            'CREATE TABLE order_checkout_sessions',
            'UNIQUE INDEX UNIQ_A72675345F37A13B (token)',
            'UNIQUE INDEX UNIQ_A72675341A314A57 (stripe_session_id)',
            'INDEX idx_checkout_user_cart_status (user_id, cart_token, status)',
            'INDEX idx_checkout_user_order_status (user_id, order_id, status)',
            'INDEX idx_checkout_status_created (status, created_at)',
            'INDEX idx_checkout_customer_email (customer_email)',
            'ALTER TABLE order_checkout_sessions ADD CONSTRAINT FK_A7267534A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE',
            'CREATE TABLE trade_in_requests',
            'UNIQUE INDEX UNIQ_E2E9A793AEA34913 (reference)',
            'INDEX idx_trade_in_status_created (status, created_at)',
            'INDEX idx_trade_in_requester_created (requester_user_id, created_at)',
            'INDEX idx_trade_in_email (email)',
            'INDEX idx_trade_in_closed_at (closed_at)',
            'ALTER TABLE trade_in_requests ADD CONSTRAINT FK_E2E9A793A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL',
            'CREATE TABLE catalog_products',
            'UNIQUE INDEX UNIQ_816D8444989D9B62 (slug)',
            'UNIQUE INDEX UNIQ_816D8444F9038C4 (sku)',
            'INDEX idx_catalog_products_publication (is_published, is_featured_home, created_at)',
            'INDEX idx_catalog_products_category_publication (category_id, is_published, created_at)',
            'INDEX idx_catalog_products_price_publication (is_published, price_cents)',
            'ALTER TABLE catalog_products ADD CONSTRAINT FK_816D844412469DE2 FOREIGN KEY (category_id) REFERENCES catalog_categories (id) ON DELETE RESTRICT',
            'ALTER TABLE catalog_products ADD CONSTRAINT FK_816D844444F5D008 FOREIGN KEY (brand_id) REFERENCES catalog_brands (id) ON DELETE SET NULL',
            'CREATE TABLE auth_refresh_tokens',
            'UNIQUE INDEX UNIQ_861C64599692E25D (selector)',
            'INDEX idx_auth_refresh_tokens_expires_at (expires_at)',
            'ALTER TABLE auth_refresh_tokens ADD CONSTRAINT FK_861C6459A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE',
            'CREATE TABLE outbox_events',
            'UNIQUE INDEX uniq_outbox_event_key (event_key)',
            'INDEX idx_outbox_pending (status, available_at, created_at)',
        ] as $needle) {
            self::assertStringContainsString($needle, $sql);
        }
    }

    /**
     * @return list<string>
     */
    private function mysqlSchemaSql(): array
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);

        $schema = $tool->getSchemaFromMetadata([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Brand::class),
            $entityManager->getClassMetadata(Category::class),
            $entityManager->getClassMetadata(Product::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderCheckoutSession::class),
            $entityManager->getClassMetadata(TradeInRequest::class),
            $entityManager->getClassMetadata(RefreshToken::class),
            $entityManager->getClassMetadata(OutboxEvent::class),
        ]);

        return $schema->toSql(new MySQLPlatform());
    }
}
