<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add categories to trainings';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE trainings ADD category VARCHAR(80) NOT NULL DEFAULT 'general'");
        $this->addSql("UPDATE trainings SET category = 'bases' WHERE slug IN ('prise-en-main-ordinateur', 'bureautique-pratique')");
        $this->addSql("UPDATE trainings SET category = 'securite' WHERE slug IN ('securite-numerique-essentielle', 'sauvegarde-classement-documents')");
        $this->addSql("UPDATE trainings SET category = 'productivite' WHERE slug IN ('messagerie-agenda-professionnel', 'decouverte-outils-collaboratifs')");
        $this->addSql("UPDATE trainings SET category = 'web' WHERE slug IN ('creer-presence-web-simple')");
        $this->addSql("UPDATE trainings SET category = 'ia' WHERE slug IN ('utiliser-ia-au-quotidien')");
        $this->addSql("UPDATE trainings SET category = 'entreprise' WHERE slug IN ('gestion-numerique-petites-entreprises', 'formation-personnalisee-sur-mesure')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trainings DROP category');
    }
}
