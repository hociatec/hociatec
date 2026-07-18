<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quote created email sent at tracking field';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $quotesTable = $schemaManager->introspectTable('quotes');

        if (!$quotesTable->hasColumn('created_email_sent_at')) {
            $this->addSql("ALTER TABLE quotes ADD created_email_sent_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $quotesTable = $schemaManager->introspectTable('quotes');

        if ($quotesTable->hasColumn('created_email_sent_at')) {
            $this->addSql('ALTER TABLE quotes DROP created_email_sent_at');
        }
    }
}
