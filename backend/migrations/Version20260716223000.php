<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair missing delivery columns on orders';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('orders');

        $sql = [];
        if (!$table->hasColumn('delivery_status')) {
            $sql[] = "ADD delivery_status VARCHAR(30) NOT NULL DEFAULT 'preparing'";
        }
        if (!$table->hasColumn('delivery_carrier')) {
            $sql[] = 'ADD delivery_carrier VARCHAR(120) DEFAULT NULL';
        }
        if (!$table->hasColumn('delivery_tracking_number')) {
            $sql[] = 'ADD delivery_tracking_number VARCHAR(120) DEFAULT NULL';
        }
        if (!$table->hasColumn('delivery_tracking_url')) {
            $sql[] = 'ADD delivery_tracking_url VARCHAR(255) DEFAULT NULL';
        }
        if (!$table->hasColumn('delivery_estimated_at')) {
            $sql[] = "ADD delivery_estimated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'";
        }
        if (!$table->hasColumn('delivery_shipped_at')) {
            $sql[] = "ADD delivery_shipped_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'";
        }
        if (!$table->hasColumn('delivery_delivered_at')) {
            $sql[] = "ADD delivery_delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'";
        }

        if ($sql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $sql));
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('orders');

        $sql = [];
        if ($table->hasColumn('delivery_status')) {
            $sql[] = 'DROP delivery_status';
        }
        if ($table->hasColumn('delivery_carrier')) {
            $sql[] = 'DROP delivery_carrier';
        }
        if ($table->hasColumn('delivery_tracking_number')) {
            $sql[] = 'DROP delivery_tracking_number';
        }
        if ($table->hasColumn('delivery_tracking_url')) {
            $sql[] = 'DROP delivery_tracking_url';
        }
        if ($table->hasColumn('delivery_estimated_at')) {
            $sql[] = 'DROP delivery_estimated_at';
        }
        if ($table->hasColumn('delivery_shipped_at')) {
            $sql[] = 'DROP delivery_shipped_at';
        }
        if ($table->hasColumn('delivery_delivered_at')) {
            $sql[] = 'DROP delivery_delivered_at';
        }

        if ($sql !== []) {
            $this->addSql('ALTER TABLE orders ' . implode(', ', $sql));
        }
    }
}
