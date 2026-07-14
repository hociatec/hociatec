<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les colonnes de jeton de reinitialisation de mot de passe sur users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD password_reset_token VARCHAR(100) DEFAULT NULL, ADD password_reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E95D7092F4 ON users (password_reset_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E95D7092F4 ON users');
        $this->addSql('ALTER TABLE users DROP password_reset_token, DROP password_reset_token_expires_at');
    }
}
