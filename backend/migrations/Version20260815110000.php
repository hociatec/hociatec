<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refresh token metadata for per-session management';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('auth_refresh_tokens');
        $sql = [];

        $this->addColumnSqlIfMissing($table, $sql, 'last_used_at', 'ADD last_used_at DATETIME DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'device_label', 'ADD device_label VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'platform_label', 'ADD platform_label VARCHAR(120) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'client_label', 'ADD client_label VARCHAR(120) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'location_label', 'ADD location_label VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'user_agent', 'ADD user_agent VARCHAR(512) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'ip_address', 'ADD ip_address VARCHAR(64) DEFAULT NULL');

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE auth_refresh_tokens '.implode(', ', $sql));
        }

        if ($table->hasColumn('last_used_at')) {
            $this->addSql('UPDATE auth_refresh_tokens SET last_used_at = created_at WHERE last_used_at IS NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('auth_refresh_tokens');
        $sql = [];

        $this->dropColumnSqlIfExists($table, $sql, 'last_used_at');
        $this->dropColumnSqlIfExists($table, $sql, 'device_label');
        $this->dropColumnSqlIfExists($table, $sql, 'platform_label');
        $this->dropColumnSqlIfExists($table, $sql, 'client_label');
        $this->dropColumnSqlIfExists($table, $sql, 'location_label');
        $this->dropColumnSqlIfExists($table, $sql, 'user_agent');
        $this->dropColumnSqlIfExists($table, $sql, 'ip_address');

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE auth_refresh_tokens '.implode(', ', $sql));
        }
    }

    /**
     * @param list<string> $sql
     */
    private function addColumnSqlIfMissing(\Doctrine\DBAL\Schema\Table $table, array &$sql, string $columnName, string $fragment): void
    {
        if (!$table->hasColumn($columnName)) {
            $sql[] = $fragment;
        }
    }

    /**
     * @param list<string> $sql
     */
    private function dropColumnSqlIfExists(\Doctrine\DBAL\Schema\Table $table, array &$sql, string $columnName): void
    {
        if ($table->hasColumn($columnName)) {
            $sql[] = 'DROP '.$columnName;
        }
    }
}
