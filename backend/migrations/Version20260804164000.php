<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804164000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme le mode de facturation heure en horaire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE quote_services SET unit = 'horaire' WHERE unit = 'heure'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE quote_services SET unit = 'heure' WHERE unit = 'horaire'");
    }
}
