<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les services existants et corrige les libellés des prestations existantes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE quote_services SET duration_value = CAST(unit AS SIGNED), duration_unit = 'hour', unit = NULL WHERE unit REGEXP '^[0-9]+$' AND (duration_value IS NULL OR duration_value = 0)");

        $this->addSql("UPDATE appointment_prestations SET name = 'Installation système et pilotes' WHERE name = 'Installation systeme et pilotes'");
        $this->addSql("UPDATE appointment_prestations SET name = 'Migration de données' WHERE name = 'Migration de donnees'");
        $this->addSql("UPDATE appointment_prestations SET name = 'Dépannage express smartphone' WHERE name = 'Depannage express smartphone'");
        $this->addSql("UPDATE appointment_prestations SET name = 'Installation réseau et NAS' WHERE name = 'Installation reseau et nas'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE quote_services SET unit = CAST(duration_value AS CHAR), duration_value = NULL, duration_unit = NULL WHERE unit IS NULL AND duration_unit = 'hour' AND duration_value IS NOT NULL");

        $this->addSql("UPDATE appointment_prestations SET name = 'Installation systeme et pilotes' WHERE name = 'Installation système et pilotes'");
        $this->addSql("UPDATE appointment_prestations SET name = 'Migration de donnees' WHERE name = 'Migration de données'");
        $this->addSql("UPDATE appointment_prestations SET name = 'Depannage express smartphone' WHERE name = 'Dépannage express smartphone'");
        $this->addSql("UPDATE appointment_prestations SET name = 'Installation reseau et nas' WHERE name = 'Installation réseau et NAS'");
    }
}
