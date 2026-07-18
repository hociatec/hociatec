<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery tracking fields to orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE orders ADD delivery_status VARCHAR(30) NOT NULL DEFAULT 'preparing', ADD delivery_carrier VARCHAR(120) DEFAULT NULL, ADD delivery_tracking_number VARCHAR(120) DEFAULT NULL, ADD delivery_tracking_url VARCHAR(255) DEFAULT NULL, ADD delivery_estimated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD delivery_shipped_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD delivery_delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP delivery_status, DROP delivery_carrier, DROP delivery_tracking_number, DROP delivery_tracking_url, DROP delivery_estimated_at, DROP delivery_shipped_at, DROP delivery_delivered_at');
    }
}
