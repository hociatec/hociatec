<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cascade delete user relations (orders, audit requests) when a user account is removed.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_ORDERS_USER');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_ORDERS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE audit_requests DROP FOREIGN KEY FK_A33F8E56C7440455');
        $this->addSql('ALTER TABLE audit_requests ADD CONSTRAINT FK_A33F8E56C7440455 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_requests DROP FOREIGN KEY FK_A33F8E56C7440455');
        $this->addSql('ALTER TABLE audit_requests ADD CONSTRAINT FK_A33F8E56C7440455 FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_ORDERS_USER');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_ORDERS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT');
    }
}
