<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815183000 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Add persistent device identifier to auth refresh tokens';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('auth_refresh_tokens');
        if ($table->hasColumn('device_identifier')) {
            return;
        }

        $this->addSql('ALTER TABLE auth_refresh_tokens ADD device_identifier VARCHAR(128) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_auth_refresh_tokens_user_device_active ON auth_refresh_tokens (user_id, device_identifier, revoked_at, expires_at)');
    }

    public function down(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('auth_refresh_tokens');
        if (!$table->hasColumn('device_identifier')) {
            return;
        }

        $this->addSql('DROP INDEX idx_auth_refresh_tokens_user_device_active ON auth_refresh_tokens');
        $this->addSql('ALTER TABLE auth_refresh_tokens DROP device_identifier');
    }
}
