<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les definitions dynamiques d attributs aux categories catalogue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE catalog_categories ADD attribute_definitions JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE catalog_categories SET attribute_definitions = '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_categories DROP attribute_definitions');
    }
}
