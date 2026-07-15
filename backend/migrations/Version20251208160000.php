<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251208160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables de notation/commentaires produit et colonnes de statistiques sur les produits.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $productColumns = $schemaManager->listTableColumns('catalog_products');
        if (!isset($productColumns['reviews_count'])) {
            $this->addSql("ALTER TABLE catalog_products ADD reviews_count INT NOT NULL DEFAULT 0");
        }
        if (!isset($productColumns['reviews_average'])) {
            $this->addSql("ALTER TABLE catalog_products ADD reviews_average DOUBLE PRECISION NOT NULL DEFAULT 0");
        }

        $tables = array_flip($schemaManager->listTableNames());
        if (!isset($tables['product_ratings'])) {
            $this->addSql("CREATE TABLE product_ratings (
                id INT AUTO_INCREMENT NOT NULL,
                product_id INT NOT NULL,
                order_item_id INT NOT NULL,
                user_id INT NOT NULL,
                score SMALLINT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'published',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                published_at DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_1D82C88A3D4C0BC4 (order_item_id),
                INDEX IDX_1D82C88A4584665A (product_id),
                INDEX IDX_1D82C88AA76ED395 (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
            $this->addSql("ALTER TABLE product_ratings ADD CONSTRAINT FK_1D82C88A4584665A FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE");
            $this->addSql("ALTER TABLE product_ratings ADD CONSTRAINT FK_1D82C88A3D4C0BC4 FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE");
            $this->addSql("ALTER TABLE product_ratings ADD CONSTRAINT FK_1D82C88AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE");
        }

        if (!isset($tables['product_comments'])) {
            $this->addSql("CREATE TABLE product_comments (
                id INT AUTO_INCREMENT NOT NULL,
                rating_id INT NOT NULL,
                body LONGTEXT NOT NULL,
                is_visible TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_35FF9D81A32EFC6D (rating_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
            $this->addSql("ALTER TABLE product_comments ADD CONSTRAINT FK_35FF9D81A32EFC6D FOREIGN KEY (rating_id) REFERENCES product_ratings (id) ON DELETE CASCADE");
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = array_flip($schemaManager->listTableNames());

        if (isset($tables['product_comments'])) {
            $this->addSql("ALTER TABLE product_comments DROP FOREIGN KEY FK_35FF9D81A32EFC6D");
            $this->addSql("DROP TABLE product_comments");
        }

        if (isset($tables['product_ratings'])) {
            $this->addSql("ALTER TABLE product_ratings DROP FOREIGN KEY FK_1D82C88A4584665A");
            $this->addSql("ALTER TABLE product_ratings DROP FOREIGN KEY FK_1D82C88A3D4C0BC4");
            $this->addSql("ALTER TABLE product_ratings DROP FOREIGN KEY FK_1D82C88AA76ED395");
            $this->addSql("DROP TABLE product_ratings");
        }

        $productColumns = $schemaManager->listTableColumns('catalog_products');
        if (isset($productColumns['reviews_count'])) {
            $this->addSql("ALTER TABLE catalog_products DROP reviews_count");
        }
        if (isset($productColumns['reviews_average'])) {
            $this->addSql("ALTER TABLE catalog_products DROP reviews_average");
        }
    }
}
