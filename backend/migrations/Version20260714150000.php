<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les dates de debut et de fin de validite aux devis.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes ADD valid_from DATE DEFAULT NULL, ADD valid_until DATE DEFAULT NULL');
        $this->addSql('UPDATE quotes SET valid_from = DATE(created_at) WHERE valid_from IS NULL');
        $this->addSql('UPDATE quotes SET valid_until = DATE_ADD(DATE(created_at), INTERVAL 30 DAY) WHERE valid_until IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes DROP valid_from, DROP valid_until');
    }
}
