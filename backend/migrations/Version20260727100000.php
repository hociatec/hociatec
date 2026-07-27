<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260727100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Create beta profiles, campaigns and bug reports'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE beta_tester_profiles (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, status VARCHAR(30) NOT NULL, availability JSON NOT NULL, motivation LONGTEXT NOT NULL, testing_experience LONGTEXT NOT NULL, bug_description_ability LONGTEXT NOT NULL, technical_knowledge LONGTEXT DEFAULT NULL, accessibility_need VARCHAR(30) NOT NULL, assistive_tools JSON NOT NULL, devices JSON NOT NULL, browsers JSON NOT NULL, testing_types JSON NOT NULL, consent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', privacy_notice_version VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_beta_profile_user (user_id), INDEX idx_beta_profile_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("ALTER TABLE beta_tester_profiles ADD CONSTRAINT FK_BETA_PROFILE_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE");
        $this->addSql("CREATE TABLE beta_campaigns (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(30) NOT NULL, starts_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ends_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE beta_bug_reports (id INT AUTO_INCREMENT NOT NULL, reporter_id INT NOT NULL, campaign_id INT DEFAULT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT NOT NULL, expected_behavior LONGTEXT DEFAULT NULL, actual_behavior LONGTEXT DEFAULT NULL, severity VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL, page_url VARCHAR(500) DEFAULT NULL, attachments JSON NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_BETA_REPORT_REPORTER (reporter_id), INDEX IDX_BETA_REPORT_CAMPAIGN (campaign_id), INDEX idx_beta_bug_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("ALTER TABLE beta_bug_reports ADD CONSTRAINT FK_BETA_REPORT_REPORTER FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE, ADD CONSTRAINT FK_BETA_REPORT_CAMPAIGN FOREIGN KEY (campaign_id) REFERENCES beta_campaigns (id) ON DELETE SET NULL");
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE beta_bug_reports'); $this->addSql('DROP TABLE beta_campaigns'); $this->addSql('DROP TABLE beta_tester_profiles'); }
}
