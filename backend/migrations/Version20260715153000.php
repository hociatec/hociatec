<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order notification email tracking fields';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $ordersTable = $schemaManager->introspectTable('orders');

        $orderColumnSql = [];
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'order_created_email_sent_at', "ADD order_created_email_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'invoice_email_sent_at', "ADD invoice_email_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'status_confirmed_email_sent_at', "ADD status_confirmed_email_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'status_delivered_email_sent_at', "ADD status_delivered_email_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addColumnSqlIfMissing($ordersTable, $orderColumnSql, 'status_cancelled_email_sent_at', "ADD status_cancelled_email_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");

        if ($orderColumnSql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $orderColumnSql));
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $ordersTable = $schemaManager->introspectTable('orders');

        $orderDropSql = [];
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'order_created_email_sent_at');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'invoice_email_sent_at');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'status_confirmed_email_sent_at');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'status_delivered_email_sent_at');
        $this->dropColumnSqlIfExists($ordersTable, $orderDropSql, 'status_cancelled_email_sent_at');

        if ($orderDropSql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $orderDropSql));
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
