<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250228120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add profile fields to users table';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users ADD address VARCHAR(255) NOT NULL, ADD postal_code VARCHAR(20) NOT NULL, ADD city VARCHAR(100) NOT NULL, ADD birth_date DATE NOT NULL, ADD phone_number VARCHAR(20) NOT NULL, ADD gender VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users DROP address, DROP postal_code, DROP city, DROP birth_date, DROP phone_number, DROP gender');
    }
}
