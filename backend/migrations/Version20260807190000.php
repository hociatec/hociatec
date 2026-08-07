<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add English product localization columns to catalog_products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD name_en VARCHAR(180) DEFAULT NULL, ADD short_description_en VARCHAR(255) DEFAULT NULL, ADD description_en LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP name_en, DROP short_description_en, DROP description_en');
    }
}
