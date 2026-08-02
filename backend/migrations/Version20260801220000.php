<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la mise en avant et les illustrations aux services.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE quote_services ADD is_featured_home TINYINT(1) DEFAULT 0 NOT NULL, ADD image_name VARCHAR(255) DEFAULT NULL, ADD image_size INT DEFAULT NULL, ADD image_alt VARCHAR(255) DEFAULT NULL, ADD image_external_url VARCHAR(2048) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE quote_services DROP is_featured_home, DROP image_name, DROP image_size, DROP image_alt, DROP image_external_url");
    }
}
