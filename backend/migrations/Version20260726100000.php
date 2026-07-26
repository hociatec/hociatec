<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track Stripe webhook events for idempotent processing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE stripe_webhook_events (id INT AUTO_INCREMENT NOT NULL, stripe_event_id VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, error_message LONGTEXT DEFAULT NULL, received_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', processed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_STRIPE_WEBHOOK_EVENT_ID (stripe_event_id), INDEX IDX_STRIPE_WEBHOOK_STATUS (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stripe_webhook_events');
    }
}
