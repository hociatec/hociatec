<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create beta tester applications table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE beta_testers (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, computer_level VARCHAR(30) NOT NULL, visual_profile VARCHAR(30) NOT NULL, assistive_technology VARCHAR(500) DEFAULT NULL, motivation LONGTEXT DEFAULT NULL, status VARCHAR(30) NOT NULL, consent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', privacy_notice_version VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_beta_testers_email (email), INDEX idx_beta_testers_created_at (created_at), INDEX idx_beta_testers_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE beta_testers');
    }
}
