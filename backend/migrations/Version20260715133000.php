<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invoice metadata and tax snapshot fields to orders';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $ordersTable = $schemaManager->introspectTable('orders');
        $orderItemsTable = $schemaManager->introspectTable('order_items');

        $orderColumnSql = [];
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'invoice_number', 'ADD invoice_number VARCHAR(30) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'invoice_status', "ADD invoice_status VARCHAR(20) NOT NULL DEFAULT 'issued'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'invoiced_at', "ADD invoiced_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_name', 'ADD billing_name VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_company', 'ADD billing_company VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_email', 'ADD billing_email VARCHAR(180) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_address', 'ADD billing_address LONGTEXT DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_postal_code', 'ADD billing_postal_code VARCHAR(20) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'billing_city', 'ADD billing_city VARCHAR(100) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'currency_code', "ADD currency_code VARCHAR(3) NOT NULL DEFAULT 'EUR'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'electronic_format', "ADD electronic_format VARCHAR(40) NOT NULL DEFAULT 'UBL-2.1'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'invoice_pdf_path', 'ADD invoice_pdf_path VARCHAR(255) DEFAULT NULL');
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'invoice_xml_path', 'ADD invoice_xml_path VARCHAR(255) DEFAULT NULL');

        if ($orderColumnSql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $orderColumnSql));
        }

        if (!$ordersTable->hasIndex('UNIQ_ORDERS_INVOICE_NUMBER')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_ORDERS_INVOICE_NUMBER ON orders (invoice_number)');
        }

        $orderItemColumnSql = [];
        $this->addColumnSqlIfMissing($orderItemsTable, $orderItemColumnSql, 'vat_rate_bps', 'ADD vat_rate_bps INT NOT NULL DEFAULT 2000');
        $this->addColumnSqlIfMissing($orderItemsTable, $orderItemColumnSql, 'line_subtotal_cents', 'ADD line_subtotal_cents INT NOT NULL DEFAULT 0');
        $this->addColumnSqlIfMissing($orderItemsTable, $orderItemColumnSql, 'line_vat_cents', 'ADD line_vat_cents INT NOT NULL DEFAULT 0');
        $this->addColumnSqlIfMissing($orderItemsTable, $orderItemColumnSql, 'line_total_cents', 'ADD line_total_cents INT NOT NULL DEFAULT 0');

        if ($orderItemColumnSql !== []) {
            $this->addSql('ALTER TABLE order_items ' . implode(', ', $orderItemColumnSql));
        }

        $this->addSql("UPDATE orders
            SET billing_name = COALESCE(billing_name, shipping_name),
                billing_address = COALESCE(billing_address, shipping_address),
                billing_postal_code = COALESCE(billing_postal_code, shipping_postal_code),
                billing_city = COALESCE(billing_city, shipping_city),
                invoiced_at = COALESCE(invoiced_at, created_at)
            WHERE invoice_status = 'issued'");

        $this->addSql("UPDATE order_items
            SET line_total_cents = CASE WHEN line_total_cents = 0 THEN unit_price_cents * quantity ELSE line_total_cents END,
                line_subtotal_cents = CASE WHEN line_subtotal_cents = 0 THEN ROUND((unit_price_cents * quantity) / 1.2) ELSE line_subtotal_cents END,
                line_vat_cents = CASE WHEN line_vat_cents = 0 THEN (unit_price_cents * quantity) - ROUND((unit_price_cents * quantity) / 1.2) ELSE line_vat_cents END
            WHERE line_total_cents = 0 OR line_subtotal_cents = 0 OR line_vat_cents = 0");
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $ordersTable = $schemaManager->introspectTable('orders');
        $orderItemsTable = $schemaManager->introspectTable('order_items');

        if ($ordersTable->hasIndex('UNIQ_ORDERS_INVOICE_NUMBER')) {
            $this->addSql('DROP INDEX UNIQ_ORDERS_INVOICE_NUMBER ON orders');
        }

        $orderDropSql = [];
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'invoice_number');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'invoice_status');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'invoiced_at');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_name');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_company');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_email');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_address');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_postal_code');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'billing_city');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'currency_code');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'electronic_format');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'invoice_pdf_path');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'invoice_xml_path');

        if ($orderDropSql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $orderDropSql));
        }

        $orderItemDropSql = [];
        $this->dropColumnSqlIfExists($orderItemsTable, $orderItemDropSql, 'vat_rate_bps');
        $this->dropColumnSqlIfExists($orderItemsTable, $orderItemDropSql, 'line_subtotal_cents');
        $this->dropColumnSqlIfExists($orderItemsTable, $orderItemDropSql, 'line_vat_cents');
        $this->dropColumnSqlIfExists($orderItemsTable, $orderItemDropSql, 'line_total_cents');

        if ($orderItemDropSql !== []) {
            $this->addSql('ALTER TABLE order_items ' . implode(', ', $orderItemDropSql));
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
