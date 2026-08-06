<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user verification columns (is_verified, verification_token, verification_token_expires_at)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE users ADD verification_token VARCHAR(100) DEFAULT NULL');
        $this->addSql("ALTER TABLE users ADD verification_token_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9FF0C10C ON users (verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E9FF0C10C ON users');
        $this->addSql('ALTER TABLE users DROP is_verified');
        $this->addSql('ALTER TABLE users DROP verification_token');
        $this->addSql('ALTER TABLE users DROP verification_token_expires_at');
    }
}
