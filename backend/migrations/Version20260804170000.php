<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create outbox events table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE outbox_events (id INT AUTO_INCREMENT NOT NULL, event_key VARCHAR(190) NOT NULL, type VARCHAR(120) NOT NULL, payload JSON NOT NULL, status VARCHAR(20) NOT NULL, attempts INT NOT NULL, available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_error LONGTEXT DEFAULT NULL, UNIQUE INDEX uniq_outbox_event_key (event_key), INDEX idx_outbox_pending (status, available_at, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_events');
    }
}
