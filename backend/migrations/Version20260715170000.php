<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cart conversion tracking to prevent duplicate checkout';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('cart_sessions');

        $sql = [];
        if (!$table->hasColumn('converted_at')) {
            $sql[] = "ADD converted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'";
        }
        if (!$table->hasColumn('converted_order_id')) {
            $sql[] = 'ADD converted_order_id INT DEFAULT NULL';
        }

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE cart_sessions '.implode(', ', $sql));
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('cart_sessions');

        $sql = [];
        if ($table->hasColumn('converted_at')) {
            $sql[] = 'DROP converted_at';
        }
        if ($table->hasColumn('converted_order_id')) {
            $sql[] = 'DROP converted_order_id';
        }

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE cart_sessions '.implode(', ', $sql));
        }
    }
}
