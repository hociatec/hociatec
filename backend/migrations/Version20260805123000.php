<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expiration to cart session tokens.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart_sessions ADD expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE cart_sessions SET expires_at = DATE_ADD(updated_at, INTERVAL 30 DAY) WHERE expires_at IS NULL');
        $this->addSql('ALTER TABLE cart_sessions CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_CART_SESSIONS_EXPIRES_AT ON cart_sessions (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CART_SESSIONS_EXPIRES_AT ON cart_sessions');
        $this->addSql('ALTER TABLE cart_sessions DROP expires_at');
    }
}
