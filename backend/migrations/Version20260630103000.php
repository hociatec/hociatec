<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la marque optionnelle et l annee de modele optionnelle sur les produits catalogue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD brand VARCHAR(80) DEFAULT NULL, ADD release_year SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP brand, DROP release_year');
    }
}
