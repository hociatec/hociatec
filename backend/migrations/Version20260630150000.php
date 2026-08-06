<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le groupe de variantes optionnel sur les produits catalogue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD variant_group VARCHAR(120) DEFAULT NULL');
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 11' WHERE slug LIKE 'iphone-11-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 11 Pro' WHERE slug LIKE 'iphone-11-pro-%' AND slug NOT LIKE 'iphone-11-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 11 Pro Max' WHERE slug LIKE 'iphone-11-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 12' WHERE slug LIKE 'iphone-12-%' AND slug NOT LIKE 'iphone-12-pro-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 12 Pro' WHERE slug LIKE 'iphone-12-pro-%' AND slug NOT LIKE 'iphone-12-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 12 Pro Max' WHERE slug LIKE 'iphone-12-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 13' WHERE slug LIKE 'iphone-13-%' AND slug NOT LIKE 'iphone-13-pro-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 13 Pro' WHERE slug LIKE 'iphone-13-pro-%' AND slug NOT LIKE 'iphone-13-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 13 Pro Max' WHERE slug LIKE 'iphone-13-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 14' WHERE slug LIKE 'iphone-14-%' AND slug NOT LIKE 'iphone-14-pro-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 14 Pro' WHERE slug LIKE 'iphone-14-pro-%' AND slug NOT LIKE 'iphone-14-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 14 Pro Max' WHERE slug LIKE 'iphone-14-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 15' WHERE slug LIKE 'iphone-15-%' AND slug NOT LIKE 'iphone-15-pro-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 15 Pro' WHERE slug LIKE 'iphone-15-pro-%' AND slug NOT LIKE 'iphone-15-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 15 Pro Max' WHERE slug LIKE 'iphone-15-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 16' WHERE slug LIKE 'iphone-16-%' AND slug NOT LIKE 'iphone-16-pro-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 16 Pro' WHERE slug LIKE 'iphone-16-pro-%' AND slug NOT LIKE 'iphone-16-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 16 Pro Max' WHERE slug LIKE 'iphone-16-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 17' WHERE slug LIKE 'iphone-17-%' AND slug NOT LIKE 'iphone-17-pro-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 17 Pro' WHERE slug LIKE 'iphone-17-pro-%' AND slug NOT LIKE 'iphone-17-pro-max-%'");
        $this->addSql("UPDATE catalog_products SET variant_group = 'iPhone 17 Pro Max' WHERE slug LIKE 'iphone-17-pro-max-%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP variant_group');
    }
}
