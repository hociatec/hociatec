<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105103500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_events table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE audit_events (id INT AUTO_INCREMENT NOT NULL, audit_id INT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT DEFAULT NULL, actor_user_id INT DEFAULT NULL, actor_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_AUDIT_EVENTS_AUDIT (audit_id), INDEX idx_audit_events_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("ALTER TABLE audit_events ADD CONSTRAINT FK_AUDIT_EVENTS_AUDIT FOREIGN KEY (audit_id) REFERENCES audit_requests (id) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_events');
    }
}

