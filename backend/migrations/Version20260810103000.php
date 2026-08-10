<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize catalog variant groups, positions and fallback prices';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql(<<<'SQL'
UPDATE catalog_products
SET variant_group = TRIM(
    REGEXP_REPLACE(
        REGEXP_REPLACE(name, '\s*\([^)]*\)\s*$', ''),
        '\s*\([^)]*\)\s*$',
        ''
    )
)
WHERE (variant_group IS NULL OR variant_group = '')
  AND (color IS NOT NULL OR storage_capacity IS NOT NULL)
SQL);

        $this->addSql(<<<'SQL'
WITH ranked_variants AS (
    SELECT
        id,
        ROW_NUMBER() OVER (
            PARTITION BY variant_group
            ORDER BY
                CASE WHEN variant_position IS NULL OR variant_position < 1 THEN 2147483647 ELSE variant_position END ASC,
                id ASC
        ) AS normalized_position
    FROM catalog_products
    WHERE variant_group IS NOT NULL AND variant_group <> ''
)
UPDATE catalog_products AS product
JOIN ranked_variants AS ranked ON ranked.id = product.id
SET product.variant_position = ranked.normalized_position
WHERE product.variant_position IS NULL
   OR product.variant_position < 1
   OR product.variant_position <> ranked.normalized_position
SQL);

        $this->addSql(<<<'SQL'
UPDATE catalog_products AS variant
JOIN (
    SELECT variant_group, MIN(price_cents) AS fallback_price_cents
    FROM catalog_products
    WHERE variant_group IS NOT NULL AND variant_group <> ''
      AND price_cents > 0
    GROUP BY variant_group
) AS grouped ON grouped.variant_group = variant.variant_group
SET variant.price_cents = grouped.fallback_price_cents
WHERE variant.price_cents <= 0
  AND variant.variant_group IS NOT NULL
  AND variant.variant_group <> ''
SQL);
    }

    public function down(Schema $schema): void
    {
        // Irreversible data normalization.
    }
}
