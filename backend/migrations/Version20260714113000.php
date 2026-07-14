<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table des marques du catalogue et initialise les marques existantes depuis les produits.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE catalog_brands (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_4F0A2B315E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO catalog_brands (name, created_at, updated_at) SELECT DISTINCT TRIM(brand) AS name, NOW(), NOW() FROM catalog_products WHERE brand IS NOT NULL AND TRIM(brand) <> \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE catalog_brands');
    }
}
