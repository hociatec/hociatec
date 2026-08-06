<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Create beta_bug_report_comments table.
 */
final class Version20260728100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create beta_bug_report_comments table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE beta_bug_report_comments (id INT AUTO_INCREMENT NOT NULL, bug_report_id INT NOT NULL, author_id INT NOT NULL, content LONGTEXT NOT NULL, createdAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_bug_report_comment_report (bug_report_id), INDEX idx_bug_report_comment_author (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beta_bug_report_comments ADD CONSTRAINT FK_BUG_REPORT_COMMENT_REPORT FOREIGN KEY (bug_report_id) REFERENCES beta_bug_reports (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beta_bug_report_comments ADD CONSTRAINT FK_BUG_REPORT_COMMENT_AUTHOR FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beta_bug_report_comments DROP FOREIGN KEY FK_BUG_REPORT_COMMENT_REPORT');
        $this->addSql('ALTER TABLE beta_bug_report_comments DROP FOREIGN KEY FK_BUG_REPORT_COMMENT_AUTHOR');
        $this->addSql('DROP TABLE beta_bug_report_comments');
    }
}
