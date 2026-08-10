<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust existing iPhone 17 variant prices so 128 Go variants are cheaper than 256 Go variants';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql(<<<'SQL'
UPDATE catalog_products
SET price_cents = CASE
    WHEN variant_group = 'iPhone 17' THEN 74900
    WHEN variant_group = 'iPhone 17 Pro' THEN 104900
    WHEN variant_group = 'iPhone 17 Pro Max' THEN 119900
    ELSE price_cents
END
WHERE storage_capacity = '128 Go'
  AND variant_group IN ('iPhone 17', 'iPhone 17 Pro', 'iPhone 17 Pro Max')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql(<<<'SQL'
UPDATE catalog_products
SET price_cents = CASE
    WHEN variant_group = 'iPhone 17' THEN 80000
    WHEN variant_group = 'iPhone 17 Pro' THEN 111000
    WHEN variant_group = 'iPhone 17 Pro Max' THEN 127500
    ELSE price_cents
END
WHERE storage_capacity = '128 Go'
  AND variant_group IN ('iPhone 17', 'iPhone 17 Pro', 'iPhone 17 Pro Max')
SQL);
    }
}
