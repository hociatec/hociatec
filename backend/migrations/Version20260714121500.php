<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace la marque texte des produits par une relation vers catalog_brands.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD brand_id INT DEFAULT NULL');
        $this->addSql('UPDATE catalog_products p INNER JOIN catalog_brands b ON LOWER(TRIM(b.name)) = LOWER(TRIM(p.brand)) SET p.brand_id = b.id WHERE p.brand IS NOT NULL AND TRIM(p.brand) <> ""');
        $this->addSql('ALTER TABLE catalog_products ADD CONSTRAINT FK_587D185844F5D008 FOREIGN KEY (brand_id) REFERENCES catalog_brands (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_587D185844F5D008 ON catalog_products (brand_id)');
        $this->addSql('ALTER TABLE catalog_products DROP brand');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD brand VARCHAR(80) DEFAULT NULL');
        $this->addSql('UPDATE catalog_products p LEFT JOIN catalog_brands b ON p.brand_id = b.id SET p.brand = b.name');
        $this->addSql('ALTER TABLE catalog_products DROP FOREIGN KEY FK_587D185844F5D008');
        $this->addSql('DROP INDEX IDX_587D185844F5D008 ON catalog_products');
        $this->addSql('ALTER TABLE catalog_products DROP brand_id');
    }
}
