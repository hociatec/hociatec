<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251029130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add discount fields to catalog_products for robust promotions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE catalog_products
                ADD discount_type VARCHAR(20) DEFAULT NULL,
                ADD discount_value INT DEFAULT NULL,
                ADD discount_starts_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                ADD discount_ends_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                ADD discount_enabled TINYINT(1) NOT NULL DEFAULT 0"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE catalog_products
                DROP discount_type,
                DROP discount_value,
                DROP discount_starts_at,
                DROP discount_ends_at,
                DROP discount_enabled'
        );
    }
}
