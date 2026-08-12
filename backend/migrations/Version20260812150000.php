<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add support request timeline history';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('ALTER TABLE support_requests ADD timeline JSON DEFAULT NULL');
        $this->addSql('UPDATE support_requests SET timeline = JSON_ARRAY() WHERE timeline IS NULL');
        $this->addSql('ALTER TABLE support_requests MODIFY timeline JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('ALTER TABLE support_requests DROP timeline');
    }
}
