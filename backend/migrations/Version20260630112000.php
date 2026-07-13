<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la capacité de stockage, la mémoire RAM et la couleur optionnelles sur les produits catalogue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE catalog_products ADD storage_capacity VARCHAR(40) DEFAULT NULL, ADD memory_ram VARCHAR(40) DEFAULT NULL, ADD color VARCHAR(60) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE catalog_products DROP storage_capacity, DROP memory_ram, DROP color");
    }
}
