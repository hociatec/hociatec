<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute suivi destinataire et envoi sur les bons de réduction';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vouchers ADD recipient_user_id INT DEFAULT NULL, ADD recipient_email VARCHAR(180) DEFAULT NULL, ADD sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vouchers DROP recipient_user_id, DROP recipient_email, DROP sent_at');
    }
}
