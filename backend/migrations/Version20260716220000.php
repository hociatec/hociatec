<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair missing Stripe metadata columns on checkout sessions';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('order_checkout_sessions');

        $sql = [];
        if (!$table->hasColumn('stripe_payment_status')) {
            $sql[] = 'ADD stripe_payment_status VARCHAR(40) DEFAULT NULL';
        }
        if (!$table->hasColumn('last_stripe_event_type')) {
            $sql[] = 'ADD last_stripe_event_type VARCHAR(80) DEFAULT NULL';
        }
        if (!$table->hasColumn('failure_code')) {
            $sql[] = 'ADD failure_code VARCHAR(120) DEFAULT NULL';
        }
        if (!$table->hasColumn('failure_message')) {
            $sql[] = 'ADD failure_message LONGTEXT DEFAULT NULL';
        }

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE order_checkout_sessions '.implode(', ', $sql));
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('order_checkout_sessions');

        $sql = [];
        if ($table->hasColumn('stripe_payment_status')) {
            $sql[] = 'DROP stripe_payment_status';
        }
        if ($table->hasColumn('last_stripe_event_type')) {
            $sql[] = 'DROP last_stripe_event_type';
        }
        if ($table->hasColumn('failure_code')) {
            $sql[] = 'DROP failure_code';
        }
        if ($table->hasColumn('failure_message')) {
            $sql[] = 'DROP failure_message';
        }

        if ([] !== $sql) {
            $this->addSql('ALTER TABLE order_checkout_sessions '.implode(', ', $sql));
        }
    }
}
