<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Stripe checkout session storage';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['order_checkout_sessions'])) {
            return;
        }

        $this->addSql("CREATE TABLE order_checkout_sessions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, cart_id INT DEFAULT NULL, cart_token VARCHAR(64) NOT NULL, shipping_address_id INT NOT NULL, stripe_session_id VARCHAR(255) NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, checkout_url LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, currency_code VARCHAR(3) NOT NULL, subtotal_price_cents INT NOT NULL, discount_amount_cents INT NOT NULL, total_price_cents INT NOT NULL, applied_promotion_name VARCHAR(140) DEFAULT NULL, applied_promotion_slug VARCHAR(140) DEFAULT NULL, customer_full_name VARCHAR(180) DEFAULT NULL, customer_email VARCHAR(180) NOT NULL, shipping_name VARCHAR(180) DEFAULT NULL, shipping_address LONGTEXT DEFAULT NULL, shipping_postal_code VARCHAR(20) DEFAULT NULL, shipping_city VARCHAR(100) DEFAULT NULL, billing_name VARCHAR(180) DEFAULT NULL, billing_company VARCHAR(180) DEFAULT NULL, billing_company_siren VARCHAR(20) DEFAULT NULL, billing_company_vat_number VARCHAR(32) DEFAULT NULL, purchase_order_number VARCHAR(80) DEFAULT NULL, billing_email VARCHAR(180) DEFAULT NULL, billing_address LONGTEXT DEFAULT NULL, billing_postal_code VARCHAR(20) DEFAULT NULL, billing_city VARCHAR(100) DEFAULT NULL, items_payload JSON NOT NULL, order_id INT DEFAULT NULL, completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_3A16E4265F37A13B (token), UNIQUE INDEX UNIQ_3A16E4264B975F1C (stripe_session_id), INDEX IDX_3A16E426A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE order_checkout_sessions ADD CONSTRAINT FK_3A16E426A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['order_checkout_sessions'])) {
            return;
        }

        $this->addSql('DROP TABLE order_checkout_sessions');
    }
}
