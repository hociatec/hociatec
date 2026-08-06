<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes on audit_requests.created_at and audit_requests.status';
    }

    public function up(Schema $schema): void
    {
        // MySQL syntax
        $this->addSql('CREATE INDEX idx_audit_requests_created_at ON audit_requests (created_at)');
        $this->addSql('CREATE INDEX idx_audit_requests_status ON audit_requests (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_audit_requests_created_at ON audit_requests');
        $this->addSql('DROP INDEX idx_audit_requests_status ON audit_requests');
    }
}
