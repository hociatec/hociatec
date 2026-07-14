<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un ordre de variante et normalise les groupes de variantes produits.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD variant_position SMALLINT NOT NULL DEFAULT 1');
        $this->addSql("UPDATE catalog_products SET variant_group = TRIM(REGEXP_REPLACE(name, '\\\\s*\\\\([^)]*\\\\)\\\\s*$', '')) WHERE variant_group IS NULL OR TRIM(variant_group) = ''");
        $this->addSql("
            UPDATE catalog_products p
            INNER JOIN (
                SELECT
                    id,
                    ROW_NUMBER() OVER (PARTITION BY variant_group ORDER BY id ASC) AS row_num
                FROM catalog_products
            ) ranked ON ranked.id = p.id
            SET p.variant_position = ranked.row_num
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP variant_position');
    }
}
