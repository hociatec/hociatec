<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add WCAG level column to audit_checklist_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE audit_checklist_items ADD level VARCHAR(10) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE audit_checklist_items DROP level");
    }
}

