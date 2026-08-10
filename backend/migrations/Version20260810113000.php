<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean duplicated storage labels in existing iPhone 17 variant names';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql(<<<'SQL'
UPDATE catalog_products
SET name = CASE
    WHEN id = 105 THEN 'iPhone 17 Pro reconditionné (Noir) (128 Go)'
    WHEN id = 106 THEN 'iPhone 17 Pro Max reconditionné (Noir) (128 Go)'
    WHEN id = 109 THEN 'iPhone 17 Pro reconditionné (Titane naturel) (128 Go)'
    WHEN id = 110 THEN 'iPhone 17 Pro Max reconditionné (Titane naturel) (128 Go)'
    ELSE name
END
WHERE id IN (105, 106, 109, 110)
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
SET name = CASE
    WHEN id = 105 THEN 'iPhone 17 Pro reconditionné (Noir) (256 Go) (128 Go)'
    WHEN id = 106 THEN 'iPhone 17 Pro Max reconditionné (Noir) (256 Go) (128 Go)'
    WHEN id = 109 THEN 'iPhone 17 Pro reconditionné (Titane naturel) (256 Go) (128 Go)'
    WHEN id = 110 THEN 'iPhone 17 Pro Max reconditionné (Titane naturel) (256 Go) (128 Go)'
    ELSE name
END
WHERE id IN (105, 106, 109, 110)
SQL);
    }
}
