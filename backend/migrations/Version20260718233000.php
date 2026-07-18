<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un seuil de stock faible configurable par produit.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $productsTable = $schemaManager->introspectTable('catalog_products');

        if (!$productsTable->hasColumn('low_stock_threshold')) {
            $this->addSql('ALTER TABLE catalog_products ADD low_stock_threshold INT NOT NULL DEFAULT 3');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $productsTable = $schemaManager->introspectTable('catalog_products');

        if ($productsTable->hasColumn('low_stock_threshold')) {
            $this->addSql('ALTER TABLE catalog_products DROP low_stock_threshold');
        }
    }
}
