<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order events table for order flow audit trail';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['order_events'])) {
            return;
        }

        $this->addSql('CREATE TABLE order_events (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT DEFAULT NULL, actor_user_id INT DEFAULT NULL, actor_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_39A19C828D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_events ADD CONSTRAINT FK_39A19C828D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['order_events'])) {
            return;
        }

        $this->addSql('DROP TABLE order_events');
    }
}
