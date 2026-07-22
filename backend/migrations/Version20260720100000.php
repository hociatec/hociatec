<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Masque les anciennes categories catalogue issues des fixtures de demonstration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE catalog_categories
SET is_visible = 0, updated_at = CURRENT_TIMESTAMP
WHERE slug IN ('pc-portables', 'pc-bureau', 'smartphones', 'tablettes', 'peripheriques')
SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
