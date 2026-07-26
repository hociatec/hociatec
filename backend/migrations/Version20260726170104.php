<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260726170104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add trade-in closure, payment, secure document and stock tracking fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE trade_in_requests ADD final_offer_cents INT DEFAULT NULL, ADD payment_method VARCHAR(30) DEFAULT NULL, ADD payment_status VARCHAR(30) NOT NULL DEFAULT 'pending', ADD transaction_reference VARCHAR(120) DEFAULT NULL, ADD paid_at DATETIME DEFAULT NULL, ADD rib_path VARCHAR(255) DEFAULT NULL, ADD rib_original_name VARCHAR(255) DEFAULT NULL, ADD rib_size INT DEFAULT NULL, ADD rib_sha256 VARCHAR(64) DEFAULT NULL, ADD receipt_path VARCHAR(255) DEFAULT NULL, ADD stock_product_id INT DEFAULT NULL, ADD stock_added_at DATETIME DEFAULT NULL, ADD closed_at DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trade_in_requests DROP final_offer_cents, DROP payment_method, DROP payment_status, DROP transaction_reference, DROP paid_at, DROP rib_path, DROP rib_original_name, DROP rib_size, DROP rib_sha256, DROP receipt_path, DROP stock_product_id, DROP stock_added_at, DROP closed_at');
    }
}
