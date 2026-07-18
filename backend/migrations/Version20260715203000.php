<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les tags admin clients JSON null vers un tableau vide';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE users SET admin_tags = '[]' WHERE admin_tags IS NULL OR JSON_TYPE(admin_tags) = 'NULL'");
    }

    public function down(Schema $schema): void
    {
    }
}
