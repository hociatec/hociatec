<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814170500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair production drift for shipping address professional columns';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('user_shipping_addresses');
        $sql = [];

        $this->addColumnSqlIfMissing($table, $sql, 'type', "ADD type VARCHAR(20) NOT NULL DEFAULT 'personal'");
        $this->addColumnSqlIfMissing($table, $sql, 'company', 'ADD company VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'company_siren', 'ADD company_siren VARCHAR(20) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $sql, 'company_vat_number', 'ADD company_vat_number VARCHAR(32) DEFAULT NULL');

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE user_shipping_addresses '.implode(', ', $sql));
        }
    }

    public function down(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('user_shipping_addresses');
        $sql = [];

        $this->dropColumnSqlIfExists($table, $sql, 'type');
        $this->dropColumnSqlIfExists($table, $sql, 'company');
        $this->dropColumnSqlIfExists($table, $sql, 'company_siren');
        $this->dropColumnSqlIfExists($table, $sql, 'company_vat_number');

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE user_shipping_addresses '.implode(', ', $sql));
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
