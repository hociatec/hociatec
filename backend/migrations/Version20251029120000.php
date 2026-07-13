<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251029120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add homepage feature flag and gallery image columns to catalog products.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE catalog_products
                ADD is_featured_home TINYINT(1) NOT NULL DEFAULT 0,
                ADD gallery_image2_name VARCHAR(255) DEFAULT NULL,
                ADD gallery_image2_size INT DEFAULT NULL,
                ADD gallery_image3_name VARCHAR(255) DEFAULT NULL,
                ADD gallery_image3_size INT DEFAULT NULL,
                ADD gallery_image4_name VARCHAR(255) DEFAULT NULL,
                ADD gallery_image4_size INT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE catalog_products
                DROP is_featured_home,
                DROP gallery_image2_name,
                DROP gallery_image2_size,
                DROP gallery_image3_name,
                DROP gallery_image3_size,
                DROP gallery_image4_name,
                DROP gallery_image4_size'
        );
    }
}
