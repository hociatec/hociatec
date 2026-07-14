<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une duree estimee optionnelle aux services.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_services ADD duration_value INT DEFAULT NULL, ADD duration_unit VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_services DROP duration_value, DROP duration_unit');
    }
}
