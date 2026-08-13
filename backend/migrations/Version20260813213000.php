<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813213000 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Drop deprecated professional billing columns from user shipping addresses';
    }

    public function up(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('user_shipping_addresses');
        $dropSql = [];

        $this->dropColumnSqlIfExists($table, $dropSql, 'company');
        $this->dropColumnSqlIfExists($table, $dropSql, 'company_siren');
        $this->dropColumnSqlIfExists($table, $dropSql, 'company_vat_number');
        $this->dropColumnSqlIfExists($table, $dropSql, 'purchase_order_number');

        if ([] !== $dropSql) {
            $this->addSql('ALTER TABLE user_shipping_addresses '.implode(', ', $dropSql));
        }
    }

    public function down(Schema $schema): void
    {
        $table = $this->connection->createSchemaManager()->introspectTable('user_shipping_addresses');
        $addSql = [];

        $this->addColumnSqlIfMissing($table, $addSql, 'company', 'ADD company VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $addSql, 'company_siren', 'ADD company_siren VARCHAR(20) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $addSql, 'company_vat_number', 'ADD company_vat_number VARCHAR(32) DEFAULT NULL');
        $this->addColumnSqlIfMissing($table, $addSql, 'purchase_order_number', 'ADD purchase_order_number VARCHAR(80) DEFAULT NULL');

        if ([] !== $addSql) {
            $this->addSql('ALTER TABLE user_shipping_addresses '.implode(', ', $addSql));
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

    /**
     * @param list<string> $sql
     */
    private function addColumnSqlIfMissing(\Doctrine\DBAL\Schema\Table $table, array &$sql, string $columnName, string $fragment): void
    {
        if (!$table->hasColumn($columnName)) {
            $sql[] = $fragment;
        }
    }
}
