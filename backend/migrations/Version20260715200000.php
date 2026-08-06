<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute notes internes et tags admin sur les clients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD admin_notes LONGTEXT DEFAULT NULL, ADD admin_tags JSON NOT NULL');
        $this->addSql("UPDATE users SET admin_tags = '[]' WHERE admin_tags IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP admin_notes, DROP admin_tags');
    }
}
