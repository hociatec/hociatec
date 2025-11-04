<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251029133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add selling_type column to catalog_products';
    }

    public function up(Schema $schema): void
    {
        // this migration is safe for MySQL
        $this->addSql("ALTER TABLE catalog_products ADD selling_type VARCHAR(10) NOT NULL DEFAULT 'sale'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP selling_type');
    }
}

