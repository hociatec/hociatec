<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track marketing campaign recipient delivery status';
    }

    public function up(Schema $schema): void
    {
        $campaigns = $schema->getTable('marketing_email_campaigns');
        if (!$campaigns->hasColumn('pending_count')) {
            $this->addSql('ALTER TABLE marketing_email_campaigns ADD pending_count INT NOT NULL, ADD sent_count INT NOT NULL, ADD failed_count INT NOT NULL, ADD skipped_count INT NOT NULL');
        }

        if (!$schema->hasTable('marketing_email_campaign_recipients')) {
            $this->addSql('CREATE TABLE marketing_email_campaign_recipients (id INT AUTO_INCREMENT NOT NULL, campaign_id INT NOT NULL, user_id INT NOT NULL, email_snapshot VARCHAR(180) NOT NULL, status VARCHAR(20) NOT NULL, failure_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', failed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', skipped_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_CAMPAIGN (campaign_id), INDEX IDX_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_USER (user_id), INDEX IDX_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_STATUS (status), UNIQUE INDEX uniq_marketing_campaign_recipient_user (campaign_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE marketing_email_campaign_recipients ADD CONSTRAINT FK_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_CAMPAIGN FOREIGN KEY (campaign_id) REFERENCES marketing_email_campaigns (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE marketing_email_campaign_recipients ADD CONSTRAINT FK_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketing_email_campaign_recipients DROP FOREIGN KEY FK_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_CAMPAIGN');
        $this->addSql('ALTER TABLE marketing_email_campaign_recipients DROP FOREIGN KEY FK_MARKETING_EMAIL_CAMPAIGN_RECIPIENT_USER');
        $this->addSql('DROP TABLE marketing_email_campaign_recipients');
        $this->addSql('ALTER TABLE marketing_email_campaigns DROP pending_count, DROP sent_count, DROP failed_count, DROP skipped_count');
    }
}
