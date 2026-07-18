<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused transactional email templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM marketing_email_templates WHERE slug IN (
            'transaction-order-invoice-issued',
            'transaction-order-status-confirmed'
        )");
    }

    public function down(Schema $schema): void
    {
    }
}
