<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit requests & checklist items (incl. accessibility type)';
    }

    public function up(Schema $schema): void
    {
        // audit_requests
        $this->addSql("CREATE TABLE audit_requests (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, number VARCHAR(30) NOT NULL, type VARCHAR(20) NOT NULL, target_url VARCHAR(255) NOT NULL, objectives LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_A33F8E569F8A7C8D (number), INDEX IDX_A33F8E56C7440455 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE audit_requests ADD CONSTRAINT FK_A33F8E56C7440455 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE RESTRICT');

        // audit_checklist_items
        $this->addSql("CREATE TABLE audit_checklist_items (id INT AUTO_INCREMENT NOT NULL, audit_id INT NOT NULL, category VARCHAR(100) NOT NULL, criterion_key VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, position INT NOT NULL, is_compliant TINYINT(1) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_8AFB06F7218E0F2 (audit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE audit_checklist_items ADD CONSTRAINT FK_8AFB06F7218E0F2 FOREIGN KEY (audit_id) REFERENCES audit_requests (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_checklist_items DROP FOREIGN KEY FK_8AFB06F7218E0F2');
        $this->addSql('ALTER TABLE audit_requests DROP FOREIGN KEY FK_A33F8E56C7440455');
        $this->addSql('DROP TABLE audit_checklist_items');
        $this->addSql('DROP TABLE audit_requests');
    }
}

