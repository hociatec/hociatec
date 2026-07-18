<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add B2B invoice recipient fields to user shipping addresses and orders';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $ordersTable = $schemaManager->introspectTable('orders');
        $addressesTable = $schemaManager->introspectTable('user_shipping_addresses');

        $orderColumnSql = [];
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_company_siren', 'ADD billing_company_siren VARCHAR(20) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_company_vat_number', 'ADD billing_company_vat_number VARCHAR(32) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'purchase_order_number', 'ADD purchase_order_number VARCHAR(80) DEFAULT NULL');

        if ($orderColumnSql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $orderColumnSql));
        }

        $addressColumnSql = [];
        $this->addColumnSqlIfMissing($addressesTable, $addressColumnSql, 'company', 'ADD company VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($addressesTable, $addressColumnSql, 'company_siren', 'ADD company_siren VARCHAR(20) DEFAULT NULL');
        $this->addColumnSqlIfMissing($addressesTable, $addressColumnSql, 'company_vat_number', 'ADD company_vat_number VARCHAR(32) DEFAULT NULL');
        $this->addColumnSqlIfMissing($addressesTable, $addressColumnSql, 'purchase_order_number', 'ADD purchase_order_number VARCHAR(80) DEFAULT NULL');

        if ($addressColumnSql !== []) {
            $this->addSql('ALTER TABLE user_shipping_addresses ' . implode(', ', $addressColumnSql));
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $ordersTable = $schemaManager->introspectTable('orders');
        $addressesTable = $schemaManager->introspectTable('user_shipping_addresses');

        $orderDropSql = [];
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_company_siren');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_company_vat_number');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'purchase_order_number');

        if ($orderDropSql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $orderDropSql));
        }

        $addressDropSql = [];
        $this->dropColumnSqlIfExists($addressesTable, $addressDropSql, 'company');
        $this->dropColumnSqlIfExists($addressesTable, $addressDropSql, 'company_siren');
        $this->dropColumnSqlIfExists($addressesTable, $addressDropSql, 'company_vat_number');
        $this->dropColumnSqlIfExists($addressesTable, $addressDropSql, 'purchase_order_number');

        if ($addressDropSql !== []) {
            $this->addSql('ALTER TABLE user_shipping_addresses ' . implode(', ', $addressDropSql));
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
            $sql[] = 'DROP ' . $columnName;
        }
    }
}
