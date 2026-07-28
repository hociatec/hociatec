<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Improve beta bug report workflow with assignment, duplicates, replies and activity journal';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beta_bug_reports ADD assigned_to_id INT DEFAULT NULL, ADD duplicate_of_id INT DEFAULT NULL, ADD duplicate_reason LONGTEXT DEFAULT NULL, ADD assigned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD duplicated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_admin_reply_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_reporter_reply_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_BETA_BUG_ASSIGNED_TO ON beta_bug_reports (assigned_to_id)');
        $this->addSql('CREATE INDEX IDX_BETA_BUG_DUPLICATE_OF ON beta_bug_reports (duplicate_of_id)');
        $this->addSql('CREATE INDEX idx_beta_bug_campaign ON beta_bug_reports (campaign_id)');
        $this->addSql('ALTER TABLE beta_bug_reports ADD CONSTRAINT FK_BETA_BUG_ASSIGNED_TO FOREIGN KEY (assigned_to_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE beta_bug_reports ADD CONSTRAINT FK_BETA_BUG_DUPLICATE_OF FOREIGN KEY (duplicate_of_id) REFERENCES beta_bug_reports (id) ON DELETE SET NULL');
        $this->addSql('CREATE TABLE beta_bug_report_activities (id INT AUTO_INCREMENT NOT NULL, bug_report_id INT NOT NULL, actor_id INT DEFAULT NULL, action VARCHAR(60) NOT NULL, from_value VARCHAR(190) DEFAULT NULL, to_value VARCHAR(190) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BETA_BUG_ACTIVITY_REPORT (bug_report_id), INDEX IDX_BETA_BUG_ACTIVITY_ACTOR (actor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beta_bug_report_activities ADD CONSTRAINT FK_BETA_BUG_ACTIVITY_REPORT FOREIGN KEY (bug_report_id) REFERENCES beta_bug_reports (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beta_bug_report_activities ADD CONSTRAINT FK_BETA_BUG_ACTIVITY_ACTOR FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('UPDATE beta_bug_reports SET last_reporter_reply_at = created_at WHERE last_reporter_reply_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beta_bug_report_activities DROP FOREIGN KEY FK_BETA_BUG_ACTIVITY_REPORT');
        $this->addSql('ALTER TABLE beta_bug_report_activities DROP FOREIGN KEY FK_BETA_BUG_ACTIVITY_ACTOR');
        $this->addSql('DROP TABLE beta_bug_report_activities');
        $this->addSql('ALTER TABLE beta_bug_reports DROP FOREIGN KEY FK_BETA_BUG_ASSIGNED_TO');
        $this->addSql('ALTER TABLE beta_bug_reports DROP FOREIGN KEY FK_BETA_BUG_DUPLICATE_OF');
        $this->addSql('DROP INDEX IDX_BETA_BUG_ASSIGNED_TO ON beta_bug_reports');
        $this->addSql('DROP INDEX IDX_BETA_BUG_DUPLICATE_OF ON beta_bug_reports');
        $this->addSql('DROP INDEX idx_beta_bug_campaign ON beta_bug_reports');
        $this->addSql('ALTER TABLE beta_bug_reports DROP assigned_to_id, DROP duplicate_of_id, DROP duplicate_reason, DROP assigned_at, DROP duplicated_at, DROP last_admin_reply_at, DROP last_reporter_reply_at');
    }
}
