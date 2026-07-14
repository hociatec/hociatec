<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une table de refresh tokens pour les sessions longues web et mobile.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE auth_refresh_tokens (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            selector VARCHAR(64) NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            revoked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_AUTH_REFRESH_TOKENS_SELECTOR (selector),
            INDEX IDX_AUTH_REFRESH_TOKENS_USER_ID (user_id),
            INDEX idx_auth_refresh_tokens_expires_at (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE auth_refresh_tokens ADD CONSTRAINT FK_AUTH_REFRESH_TOKENS_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE auth_refresh_tokens');
    }
}
